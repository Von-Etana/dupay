PUB=pub_W7SWM8o1uHhAbhrnqc8KlQQou0kjAaOYWgh2sPxd
CID=6a7c8fdf5f566a157cc4bc63

DETAIL=$(curl -s "https://strowallet.com/api/bitvcard/fetch-nfccard-detail?public_key=$PUB&card_id=$CID")
echo "$DETAIL" | python3 -c "import sys,json; cd=json.load(sys.stdin).get('response',{}).get('card_detail',{}); print('card_status:', cd.get('card_status')); print('card_number_url:', cd.get('card_number_url'))"

URL=$(echo "$DETAIL" | python3 -c "import sys,json; print(json.load(sys.stdin).get('response',{}).get('card_detail',{}).get('card_number_url',''))")
echo '--- raw response from GET of that URL ---'
curl -s "$URL"; echo