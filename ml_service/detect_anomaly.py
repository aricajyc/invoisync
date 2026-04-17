#!/usr/bin/env python3
import sys
import json
import math

def detect_anomaly(invoice_data):
    """
    Simple rule-based 'AI' that mimics ML behavior
    In FYP2, this will be replaced with actual Isolation Forest
    """
    
    amount = float(invoice_data.get('total_amount', 0))
    tax = float(invoice_data.get('tax_amount', 0))
    line_items = int(invoice_data.get('line_items', 1))
    
    is_anomaly = False
    anomaly_reasons = []
    confidence = 0.0
    
    # Check 1: Suspicious round numbers
    if amount >= 5000 and amount % 1000 == 0:
        is_anomaly = True
        anomaly_reasons.append("Suspicious round number pattern detected")
        confidence += 0.35
    
    # Check 2: Unusual tax rate
    expected_tax = amount * 0.06  # Standard 6% SST in Malaysia
    tax_deviation = abs(tax - expected_tax) / expected_tax if expected_tax > 0 else 0
    
    if tax_deviation > 0.10:  # More than 10% deviation
        is_anomaly = True
        anomaly_reasons.append(f"Tax rate deviation detected (expected: RM{expected_tax:.2f}, actual: RM{tax:.2f})")
        confidence += 0.40
    
    # Check 3: Unusually high amount per line item
    if line_items > 0:
        avg_per_item = amount / line_items
        if avg_per_item > 10000:
            is_anomaly = True
            anomaly_reasons.append(f"Unusually high amount per line item (RM{avg_per_item:.2f})")
            confidence += 0.25
    
    # Check 4: Very low amounts (potential invoice splitting)
    if amount < 50 and line_items > 0:
        is_anomaly = True
        anomaly_reasons.append("Unusually low invoice amount - possible invoice splitting")
        confidence += 0.30
    
    # Check 5: Zero tax on large amounts
    if amount > 1000 and tax == 0:
        is_anomaly = True
        anomaly_reasons.append("Zero tax on significant transaction - requires verification")
        confidence += 0.45
    
    # Cap confidence at 0.95 (ML never 100% certain)
    confidence = min(confidence, 0.95)
    
    # Calculate anomaly score (-1 to 1, like Isolation Forest)
    anomaly_score = -confidence if is_anomaly else (1 - confidence)
    
    return {
        'is_anomaly': is_anomaly,
        'confidence': round(confidence, 2),
        'anomaly_score': round(anomaly_score, 3),
        'reasons': anomaly_reasons,
        'status': 'warning' if is_anomaly else 'normal',
        'recommendation': get_recommendation(is_anomaly, anomaly_reasons)
    }

def get_recommendation(is_anomaly, reasons):
    """Generate user-friendly recommendations"""
    if not is_anomaly:
        return "Invoice pattern appears normal"
    
    recommendations = []
    
    for reason in reasons:
        if "round number" in reason.lower():
            recommendations.append("Verify if this exact amount is intentional")
        elif "tax rate" in reason.lower():
            recommendations.append("Check tax calculation - standard SST is 6%")
        elif "per line item" in reason.lower():
            recommendations.append("Review individual line items for accuracy")
        elif "splitting" in reason.lower():
            recommendations.append("Ensure this is not part of split transactions")
        elif "zero tax" in reason.lower():
            recommendations.append("Confirm if transaction is tax-exempt")
    
    return " | ".join(recommendations)

if __name__ == '__main__':
    # Read JSON from command line argument
    try:
        input_data = json.loads(sys.argv[1])
        result = detect_anomaly(input_data)
        print(json.dumps(result))
    except Exception as e:
        error_result = {
            'is_anomaly': False,
            'confidence': 0.0,
            'anomaly_score': 0.0,
            'reasons': [],
            'status': 'error',
            'error': str(e)
        }
        print(json.dumps(error_result))