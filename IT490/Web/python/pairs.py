import requests
import json

# Kraken API endpoint for asset pairs
url = 'https://api.kraken.com/0/public/AssetPairs'

response = requests.get(url)
pairs = response.json()

# Filter pairs containing BTC (XBT) and ETH
btc = {key: value for key, value in pairs['result'].items() if 'XETH' in key}

print(json.dumps(btc, indent=4))