const fetch = require('node-fetch');

(async () => {
    const params = new URLSearchParams();
    params.append('getWastePackaged', 'true');
    params.append('id_office', '3');
    
    try {
        const res = await fetch('http://localhost:3000/ajax/pos.ajax.php', {
            method: 'POST',
            body: params.toString(),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODE0NjUwNjIsImV4cCI6MTc4MTU1MTQ2MiwiZGF0YSI6eyJpZCI6MSwiZW1haWwiOiJzdXBlcmFkbWluQHBvcy5jb20ifX0.UzazCfvoagQbqAd6Wqj6evofDjE8SYGiX-w_-emOjgA'
            }
        });
        
        console.log("Status:", res.status);
        const text = await res.text();
        console.log("Response:", text);
    } catch(e) {
        console.error(e);
    }
})();
