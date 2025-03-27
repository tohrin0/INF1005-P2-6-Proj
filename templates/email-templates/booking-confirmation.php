<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        p {
            line-height: 1.6;
            color: #555;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Booking Confirmation</h1>
        <p>Dear <?php echo htmlspecialchars($userName); ?>,</p>
        <p>Thank you for booking your flight with us! Here are your booking details:</p>
        <p><strong>Flight Number:</strong> <?php echo htmlspecialchars($flightNumber); ?></p>
        <p><strong>Departure:</strong> <?php echo htmlspecialchars($departure); ?></p>
        <p><strong>Arrival:</strong> <?php echo htmlspecialchars($arrival); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($date); ?></p>
        <p><strong>Time:</strong> <?php echo htmlspecialchars($time); ?></p>
        <p>If you have any questions or need further assistance, please do not hesitate to contact us.</p>
        <p>Safe travels!</p>
        <div class="footer">
            <p>Best regards,</p>
            <p>The Flight Booking Team</p>
        </div>
    </div>
</body>
</html>