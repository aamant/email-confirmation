<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Email confirmation</h2>

		<div>
			To confirm your email, follow this link: {{ URL::to('email/confirmation', array($token)) }}.
		</div>
	</body>
</html>