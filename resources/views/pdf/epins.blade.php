<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recharge Cards</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .logo { width: 150px; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <h1>DUPAY</h1>
        <!--<img src="https://mydupay.com/assets/images/full-logo.jpeg" class="logo" alt="Company Logo">-->
        <h2>Recharge Cards - Receipt</h2>
    </div>
    
    <h2>ePIN Purchase Receipt</h2>
    <p><strong>Network:</strong> {{ $network }}</p>
    <p><strong>Value:</strong> ₦{{ $value }}</p>
    <p><strong>Quantity:</strong> {{ $quantity }}</p>
    <p><strong>Reference:</strong> {{ $reference }}</p>
    <p><strong>Date:</strong> {{ $date->format('Y-m-d H:i') }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>PIN</th>
                <th>Serial</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cards as $index => $card)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $card['pin'] }}</td>
                    <td>{{ $card['serial'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
