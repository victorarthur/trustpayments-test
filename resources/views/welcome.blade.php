<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no,maximum-scale=1.0">
</head>
<body>
<div id="st-notification-frame"></div>
<form id="st-form" action="https://test.noobru.com/process-payment" method="POST">
    <div id="st-card-number"></div>
    <div id="st-expiration-date"></div>
    <div id="st-security-code"></div>
    <button type="submit">Pay securely</button>
</form>
<script src="https://cdn.eu.trustpayments.com/js/latest/st.js"></script>
<script>
	let jwt_data = {
		'payload': {
			"accounttypedescription": "ECOM",
			"sitereference": "test_noolabs127216",
			"requesttypedescriptions": ["THREEDQUERY", "AUTH"],
			"customeremail": "ilyen85@proton.me",
			"customertelephone": "+3606709422082",
			"customertown": "Veszprémvarsány",
			"customercounty": null,
			"customerstreet": "Rákóczi Ferenc utca 128.",
			"customercountryiso2a": "HU",
			"customerfirstname": "Bertalan",
			"customerlastname": "Sándor",
			"customerpostcode": "8438",
			"billingemail": "ilyen85@proton.me",
			"billingtelephone": "+3606709422082",
			"billingtown": "Veszprémvarsány",
			"billingcounty": null,
			"billingstreet": "Rákóczi Ferenc utca 128.",
			"billingcountryiso2a": "HU",
			"billingfirstname": "Bertalan",
			"billinglastname": "Sándor",
			"billingpostcode": "8438",
			"baseamount": 5684,
			"currencyiso3a": "USD",
			"orderreference": 2058309,
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
	.then(function (res) {
		return res.text();
	})
	.then(generated_jwt => {
		console.log(`TrustPayments - generated_jwt: ${JSON.stringify(generated_jwt)}`)

		var st = SecureTrading({
			jwt: generated_jwt
		});
		st.Components();

		st.on('paymentPreCheck', (data) => {
			console.log('TrustPayments - st paymentPreCheck event - data: ', JSON.stringify(data))
		})
		//Called when the payment initialisation has started.
		st.on('paymentInitStarted', (data) => {
			console.log('TrustPayments - st paymentInitStarted event - data: ', JSON.stringify(data))
		})
		//Called when the payment initialisation has successfully completed.
		st.on('paymentInitCompleted', (data) => {
			console.log('TrustPayments - st paymentInitCompleted event - data: ', JSON.stringify(data))
		})
		//Called when the payment initialisation has encountered an error.
		st.on('paymentInitFailed', (data) => {
			console.log('TrustPayments - st paymentInitFailed event - data: ', JSON.stringify(data))
		})
		//Called when the payment process has started.
		st.on('paymentStarted', (data) => {
			console.log('TrustPayments - st paymentStarted event - data: ', JSON.stringify(data))
		})
		//Called when the payment has reached the gateway.
		st.on('paymentCompleted', (data) => {
			console.log('TrustPayments - st paymentCompleted event - data: ', JSON.stringify(data))
		})
		//Called when the payment has failed.
		st.on('paymentFailed', (data) => {
			console.log('TrustPayments - st paymentFailed event - data: ', JSON.stringify(data))
		})
		//Called when the user has cancelled the payment before it has reached the gateway.
		st.on('paymentCanceled', (data) => {
			console.log('TrustPayments - st paymentCanceled event - data: ', JSON.stringify(data))
		})
	})
</script>
</body>
</html>
