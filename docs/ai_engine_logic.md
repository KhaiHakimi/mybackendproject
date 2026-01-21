# Simple Explanation: How Ferrycast Calculates Risk

This document describes the logic behind the Ferrycast AI Engine in simple, plain English. Think of the AI as a **Virtual Safety Officer** that learns from maritime safety rules.

## 1. What data does it look at? (The Inputs)
Just like a captain looks out the window, the AI looks at three main numbers:
1.  **Wind Speed** (How fast the wind is blowing)
2.  **Wave Height** (How tall the waves are)
3.  **Visibility** (How far you can see)

## 2. Who taught the AI? (The Rules)
We taught the AI using standard maritime safety rules. You can explain it like a Traffic Light system:

| Zone | Color | Meaning | Condition (The "Rule") |
| :--- | :--- | :--- | :--- |
| **Danger** | 🔴 **RED** | **STOP / CANCEL** | **Wind > 50 km/h** OR **Waves > 3.5m**<br>(Severe Storms) |
| **Caution** | 🟡 **AMBER** | **BE CAREFUL** | **Wind > 30 km/h** OR **Waves > 2.0m**<br>(Rough Waters) |
| **Safe** | 🟢 **GREEN** | **GO** | Every other normal condition. |

## 3. How does it calculate the "Risk Score"?
The AI doesn't just say "Yes" or "No". It calculates a **Probability %** (How sure it is).

### The Simple Formula
We calculate a single **Risk Score (0 to 100%)** to show the user.

> **Risk Score = (Danger % x 100) + (Caution % x 40)**

**Why multiply Caution by 40?**
Because "Caution" conditions don't always mean a cancellation. It usually means a ~40% chance the captain will decide not to sail. Danger conditions mean a 100% chance of cancellation.

### Real World Example
Imagine the weather is "somewhat rough". The AI analyses it and says:
*   *"I am **50% sure** this is Dangerous."*
*   *"I am **50% sure** this is just Cautionary."*

**The Math:**
*   $50\% \times 100 = 50$
*   $50\% \times 40 = 20$
*   **Total Risk Score = 70%**

## 4. The Final Verdict
Based on that final score, we show the user a badge:

*   **HIGH RISK** 🔴 (Score > 75%): Extremely likely to cancel.
*   **CAUTION** 🟡 (Score > 30%): Might cancel or be delayed.
*   **SAFE** 🟢 (Score < 30%): Good to go.
