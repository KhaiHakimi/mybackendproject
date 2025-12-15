from flask import Flask, request, jsonify
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
import joblib
import os

app = Flask(__name__)

# --- Random Forest Model Initialization ---
# Since we don't have a historical database yet, we will train the model
# on SYNTHETIC data every time the service starts.
# In production, you would load a saved model: model = joblib.load('risk_model.pkl')

import threading
import time

# =========================================================
# GLOBAL TRAINING STATE
# =========================================================
# These variables track the continuous learning process in the background.
training_active = True    # Switch to stop the training loop
training_cycle = 0        # Counter for how many "generations" of training we've done
global_X = pd.DataFrame() # The accumulating textbook (Dataset)
global_y = pd.Series(dtype=int) # The answers (Risk Labels)

# =========================================================
# SYNTHETIC DATA GENERATOR (THE SIMULATOR)
# =========================================================
# Since we don't have years of real historical ferry crash data,
# we simulate physically realistic scenarios to teach the AI what "Danger" looks like.
def generate_synthetic_batch(batch_size=5000):
    np.random.seed(int(time.time())) # Ensure randomness every time
    
    # Generate random weather conditions (Features)
    winds = np.random.uniform(0, 90, batch_size) # 0-90 km/h
    waves = np.random.uniform(0, 8.0, batch_size) # 0-8 meters
    visibility = np.random.uniform(0, 30, batch_size) # 0-30 km visible
    
    risk_labels = []
    
    # "Ground Truth" Logic
    # This acts as the expert human labeling the data.
    # The AI doesn't see this code; it has to learn these rules by looking at the data.
    for i in range(batch_size):
        w = winds[i]
        h = waves[i]
        v = visibility[i]
        
        # EXACT CANCELLATION CRITERIA (Maritime Standards)
        # Class 2: "High Risk" -> MANDATORY CANCELLATION
        # - Wind > 50 km/h (Strong Gale/Storm)
        # - Waves > 3.5m (Very Rough)
        # - Visibility < 1.0 km (Severe Fog)
        if w > 50 or h > 3.5 or v < 1.0:
            risk_labels.append(2)
            
        # Class 1: "Caution" -> POSSIBLE CANCELLATION (Captain's Discretion)
        # - Wind > 30 km/h (Fresh Breeze)
        # - Waves > 2.0m (Moderate/Rough)
        # - Visibility < 5.0 km (Mist)
        # Also combination: High Wind + Moderate Wave
        elif w > 30 or h > 2.0 or v < 5.0:
            risk_labels.append(1)
            
        # Class 0: "Safe" -> NO CANCELLATION
        else:
            risk_labels.append(0)
            
    df = pd.DataFrame({
        'wind_speed': winds,
        'wave_height': waves,
        'visibility': visibility
    })
    
    return df, pd.Series(risk_labels)

# ... (Continuous Training Worker remains same) ...
# ...

@app.route('/predict-risk', methods=['POST'])
def predict_risk():
    try:
        data = request.json
        
        # Extract features
        wind_speed = float(data.get('wind_speed', 0))
        wave_height = float(data.get('wave_height', 0))
        visibility = float(data.get('visibility', 10))
        
        # Prepare input for model
        input_data = pd.DataFrame([[wind_speed, wave_height, visibility]], 
                                columns=['wind_speed', 'wave_height', 'visibility'])
        
        # ASK THE AI: "What are the probabilities?"
        # probs[0] = Probability of Safe
        # probs[1] = Probability of Caution (Captain's Discretion ~40% chance of cancel)
        # probs[2] = Probability of High Risk (Mandatory Cancellation ~100% chance)
        probs = model.predict_proba(input_data)[0]
        
        # CALCULATE EXACT CANCELLATION PERCENTAGE
        # Formula: P(HighRisk) + (P(Caution) * 0.4)
        # This gives a precise estimated probability that the ferry will not run.
        cancellation_prob = (probs[2] * 100) + (probs[1] * 40)
        
        # Cap at 99.9% unless absolutely certain, floor at 0.1% unless perfect
        cancellation_prob = min(max(cancellation_prob, 0.1), 99.9)
        
        # Determine Status Label
        status = 'Safe'
        if cancellation_prob > 75:
            status = 'High Risk'
        elif cancellation_prob > 30:
            status = 'Caution'
            
        return jsonify({
            'risk_score': round(cancellation_prob, 2), # Displayed as Percentage
            'risk_status': status,
            'algorithm': f'Random Forest (Continuous Learning - Cycle {training_cycle})',
            'dataset_size': len(global_X),
            'debug_probs': { 'safe': round(probs[0],2), 'caution': round(probs[1],2), 'high': round(probs[2],2) }
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    try:
        app.run(port=5000, debug=False) # Debug=False to avoid reloader spawning duplicate threads
    except KeyboardInterrupt:
        training_active = False
        print("Stopping training...")
