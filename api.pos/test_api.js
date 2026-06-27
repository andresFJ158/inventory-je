const token = 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy';
fetch(`http://localhost:8081/api/warehouses?token=${token}`)
  .then(res => res.json())
  .then(data => console.log(JSON.stringify(data, null, 2)))
  .catch(err => console.error(err));
