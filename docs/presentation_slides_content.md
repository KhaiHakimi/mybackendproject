# Presentation Slides Content: Ferrycast AI Engine

Use these concise points for your slide deck.

---

## Slide 1: The Concept
**Title:** The "Virtual Safety Officer"
*   **Goal:** Predict ferry cancellations before they happen.
*   **How:** It learns from maritime safety regulations.
*   **Analogy:** It works like a **Traffic Light System** (Red, Amber, Green).

---

## Slide 2: The Inputs
**Title:** 3 Key Risk Factors
The AI monitors three main weather conditions:
1.  **💨 Wind Speed** (Strength of the gale)
2.  **🌊 Wave Height** (Roughness of the sea)
3.  **👁️ Visibility** (Fog or heavy rain)

---

## Slide 3: The Rules (Traffic Light Logic)
**Title:** How It Classifies Danger

| Status | Condition | Meaning |
| :--- | :--- | :--- |
| 🔴 **RED** | **Severe Storm**<br>(Wind > 50km/h OR Waves > 3.5m) | **100%** Cancel Chance |
| 🟡 **AMBER** | **Rough / Choppy**<br>(Wind > 30km/h OR Waves > 2.0m) | **~40%** Cancel Chance |
| 🟢 **GREEN** | **Calm / Normal** | **0%** Cancel Chance |

---

## Slide 4: The Calculation
**Title:** Calculating the Risk Score
The AI calculates a single percentage (0-100%) using this weighted formula:

$$ \text{Risk Score} = (\text{Danger\%} \times 100) + (\text{Caution\%} \times 40) $$

*   **Why 40?** Because "Caution" conditions result in a cancellation only about 40% of the time (Captain's discretion).

---

## Slide 5: The User Output
**Title:** What the User Sees
We translate the mathematical score into simple decision badges:

*   **Result > 75%** → 🔴 **HIGH RISK** (Do Not Travel)
*   **Result > 30%** → 🟡 **CAUTION** (Expect Delays)
*   **Result < 30%** → 🟢 **SAFE** (Good to Go)
