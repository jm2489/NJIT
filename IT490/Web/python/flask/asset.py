from flask import Flask, jsonify, request, render_template
import os
import requests
import time
import hashlib
import hmac
import base64
from dotenv import load_dotenv
import urllib.parse

# Load environment variables
load_dotenv("/var/www/html/flask/k_API.env")

app = Flask(__name__)

# Function to make authenticated requests to Kraken API
def kraken_private_request(uri_path, data):
    url = f"https://api.kraken.com{uri_path}"
    api_key = os.getenv('KRAKEN_API_KEY')
    api_secret = os.getenv('KRAKEN_API_SECRET')

    if not api_key or not api_secret:
        return {"error": "API key or secret not loaded. Check k_API.env file."}

    # Generate a nonce automatically
    data['nonce'] = str(int(time.time() * 1000000))

    # Encode data for API-Sign
    postdata = urllib.parse.urlencode(data)
    encoded = (data['nonce'] + postdata).encode()
    message = uri_path.encode() + hashlib.sha256(encoded).digest()
    signature = hmac.new(base64.b64decode(api_secret), message, hashlib.sha512)
    api_sign = base64.b64encode(signature.digest()).decode()

    # Headers for the request
    headers = {
        'API-Key': api_key,
        'API-Sign': api_sign
    }

    # Send the POST request
    response = requests.post(url, headers=headers, data=postdata)
    return response.json()

@app.route('/')
def index():
    
    # Get current holdings (portfolio) from Kraken API
    balance_response = kraken_private_request('/0/private/Balance', {})

    if 'error' in balance_response and balance_response['error']:
        return f"Error fetching balance data: {balance_response['error']}", 500

    # Extract balances from the response
    balances = balance_response.get('result', {})
    
    # Filter assets with a non dust balance
    portfolio = {asset for asset, amount in balances.items() if float(amount) > 0}
    
    print(portfolio)
    # Fetch asset pairs from Kraken API
    asset_pairs_response = requests.get("https://api.kraken.com/0/public/AssetPairs")
    asset_pairs = asset_pairs_response.json().get('result', {})
    
    # Filter pairs based on portfolio holdings and extract base currencies
    base_currencies = sorted({
        details['base'] for details in asset_pairs.values()
        if details['base'] in portfolio
    })

    return render_template('index.html', base_currencies=base_currencies)



@app.route('/get_quote_currencies', methods=['GET'])
def get_quote_currencies():
    """Return quote currencies based on selected base currency."""
    base_token = request.args.get('base', '').upper()
    asset_pairs_response = requests.get("https://api.kraken.com/0/public/AssetPairs")
    asset_pairs = asset_pairs_response.json().get('result', {})

    # Filter asset pairs by matching the full base symbol and get unique quote currencies
    quote_currencies = sorted({
        details['quote'] for details in asset_pairs.values()
        if details['base'] == base_token
    })
    
    return jsonify(quote_currencies)

@app.route('/get_pair_details', methods=['GET'])
def get_pair_details():
    """Return details for a specific asset pair based on base and quote currencies."""
    base_token = request.args.get('base', '').upper()
    quote_token = request.args.get('quote', '').upper()
    asset_pairs_response = requests.get("https://api.kraken.com/0/public/AssetPairs")
    asset_pairs = asset_pairs_response.json().get('result', {})
    
    # Find the specific asset pair matching the base and quote tokens
    for pair, details in asset_pairs.items():
        if details['base'] == base_token and details['quote'] == quote_token:
            return jsonify(details)

    return jsonify({'error': 'Asset pair not found'}), 404

if __name__ == '__main__':
    app.run(debug=True)
