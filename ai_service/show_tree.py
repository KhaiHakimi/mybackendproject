import os
# pyrefly: ignore [missing-import]
import joblib
from sklearn.tree import export_text
from model import FEATURE_NAMES

MODEL_PATH = os.path.join(os.path.dirname(__file__), 'trained_model', 'route_cancellation_rf.pkl')

if os.path.exists(MODEL_PATH):
    model = joblib.load(MODEL_PATH)
    # A Random Forest has many trees (estimators). We'll pick the first one.
    tree = model.estimators_[0]
    
    # Export the tree as text, limiting depth so it's readable
    tree_text = export_text(tree, feature_names=FEATURE_NAMES, max_depth=4)
    print("Here is a visual representation of one of the Decision Trees in your Random Forest:")
    print("--------------------------------------------------------------------------------")
    print(tree_text)
    print("--------------------------------------------------------------------------------")
else:
    print("Model file not found. Have you trained it yet?")
