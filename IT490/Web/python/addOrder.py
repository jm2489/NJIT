import os
import requests
import time
import hashlib
import hmac
import base64
from dotenv import load_dotenv
import urllib.parse

# Load environment variables from k_API.env file
load_dotenv("k_Trade_API.env")

# Kraken API endpoint for placing an order
url = "https://api.kraken.com/0/private/AddOrder"

# Retrieve the API key and secret
api_key = os.getenv('KRAKEN_API_KEY')
api_secret = os.getenv('KRAKEN_API_SECRET')

# Generate a unique nonce
nonce = str(int(time.time() * 1000000))

# Define order parameters
data = {                           # Data for the request. This was pulled from the kraken API documentation   
    'nonce': nonce,                # Unique identifier for the request
    'ordertype': 'limit',          # Order type (e.g., 'market', 'limit')
    'type': 'buy',                 # Buy or sell
    'volume': '0.002',             # Amount of the asset to trade
    'pair': 'XETHXXBT',            # ETH to BTC trading pair on Kraken
    'price': '0.01'                # Price for the limit order (set low for testing)
}

# Encode data for API-Sign
postdata = urllib.parse.urlencode(data)
encoded = (nonce + postdata).encode()
message = b"/0/private/AddOrder" + hashlib.sha256(encoded).digest()
signature = hmac.new(base64.b64decode(api_secret), message, hashlib.sha512)
api_sign = base64.b64encode(signature.digest()).decode()

# Headers for the request
headers = {
    'API-Key': api_key,
    'API-Sign': api_sign
}
response = requests.post(url, headers=headers, data=postdata)

# Print the response
print(response.json())
