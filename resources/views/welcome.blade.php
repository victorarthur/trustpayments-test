<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no,maximum-scale=1.0">
</head>
<body>
	<div id="st-notification-frame"></div>
	{{-- <form id="st-form" action="/trustpayments-checkout" method="POST"> --}}
	{{-- Mark suggested to use the CDN endpoint as the action param, however the documentation said a merchant hosted endpoint --}}
	<form id="st-form" action="https://cdn.eu.trustpayments.com/js/latest/st.js" method="POST">
		<div id="st-card-number"></div>
		<div id="st-expiration-date"></div>
		<div id="st-security-code"></div>
		<button type="submit">Pay securely</button>
	</form>
	<script src="https://cdn.eu.trustpayments.com/js/latest/st.js"></script>
	<script>
		let jwt_data = {
			'payload':{
				'accounttypedescription':'ECOM',
				'baseamount':'1050',
				'currencyiso3a':'GBP',
				'sitereference':'test_noolabs127216',
				'requesttypedescriptions': ['THREEDQUERY', 'AUTH'],
				'billingfirstname': 'Bertalan',
				'billinglastname': 'Sándor',
				'locale': 'en_GB'
			},
			'iat': parseInt(Date.now() / 1000),
			'iss': 'jwt@noolabstrading.com',
		}
		let encoded_data = JSON.stringify(jwt_data);

		let xcsrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
		fetch('jwt-generate', {
			method: "POST",
			mode: "cors",
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-CSRF-TOKEN': xcsrf,
			},
			body: encoded_data,
		})
		.then(function(res){ return res.text();})
		.then(generated_jwt => {
			console.log(`TrustPayments - generated_jwt: ${JSON.stringify(generated_jwt)}`)
			/*"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwYXlsb2FkIjp7ImFjY291bnR0eXBlZGVzY3JpcHRpb24iOiJFQ09NIiwiYmFzZWFtb3VudCI6IjEwNTAiLCJjdXJyZW5jeWlzbzNhIjoiR0JQIiwic2l0ZXJlZmVyZW5jZSI6InRlc3Rfbm9vbGFiczEyNzIxNiIsInJlcXVlc3R0eXBlZGVzY3JpcHRpb25zIjpbIkFVVEgiXSwiYmlsbGluZ2ZpcnN0bmFtZSI6IkJlcnRhbGFuIiwiYmlsbGluZ2xhc3RuYW1lIjoiU1x1MDBlMW5kb3IiLCJsb2NhbGUiOiJlbl9HQiJ9LCJpYXQiOjE3MTkyMzg0NjMsImlzcyI6Imp3dEBub29sYWJzdHJhZGluZy5jb20ifQ.t1V91OjJWJki1Hh4QdpOB9yuJkDN1A/sR7iYAw1EGms="*/
			var st = SecureTrading({
				jwt : generated_jwt
			});
			st.Components();
		})
	</script>
</body>
</html>


