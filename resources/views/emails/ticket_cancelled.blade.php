<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Urgent: Your Ferry Booking has been Cancelled Due to Weather</title>
    <style>
        body { font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; color: #334155; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 60px; padding-top: 40px;}
        .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header-banner { 
            background-color: #0f172a; 
            background-image: url('{{ asset('images/ferry.jpeg') }}'); 
            background-size: cover; 
            background-position: center; 
            padding: 50px 20px; 
            text-align: center; 
            position: relative;
        }
        .header-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(rgba(15, 23, 42, 0.6), rgba(15, 23, 42, 0.9));
        }
        .header-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo { width: auto; height: 48px; margin-bottom: 10px; }
        .header-title { color: #ffffff; font-size: 24px; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        
        .content { padding: 40px; line-height: 1.6; font-size: 16px; }
        .greeting { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 20px; margin-top: 0;}
        
        .alert-box { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px 20px; margin-bottom: 25px; border-radius: 0 6px 6px 0; }
        .alert-text { color: #991b1b; margin: 0; font-weight: 500; font-size: 15px; }
        
        .details-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
        .details-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; margin-top: 0; margin-bottom: 15px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; }
        .details-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .label { font-weight: 500; color: #64748b; font-size: 15px;}
        .value { font-weight: 600; color: #0f172a; font-size: 15px; text-align: right;}
        
        .refund-info { font-size: 15px; color: #334155; background: #f1f5f9; padding: 20px; border-radius: 8px;}
        
        .btn-container { text-align: center; margin-top: 35px; margin-bottom: 10px; }
        .btn { display: inline-block; background-color: #0284c7; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; transition: background-color 0.2s; box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.2); }
        .btn:hover { background-color: #0369a1; }
        
        .footer { background-color: #ffffff; padding: 30px 40px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
        .footer p { margin: 5px 0; }
        
        @media only screen and (max-width: 600px) {
            .wrapper { padding-top: 0; padding-bottom: 0; }
            .main { border-radius: 0; box-shadow: none; }
            .content { padding: 30px 20px; }
            .details-row { flex-direction: column; }
            .value { text-align: left; margin-top: 4px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header-banner">
                <div class="header-content">
                    <img src="{{ asset('images/logo.png') }}" alt="FerryCast Logo" class="logo" onerror="this.style.display='none'">
                    <h1 class="header-title">FerryCast</h1>
                </div>
            </div>
            
            <div class="content">
                <p class="greeting">Hi {{ $booking->passenger_name }},</p>
                
                <div class="alert-box">
                    <p class="alert-text"><strong>Urgent Update:</strong> Your upcoming ferry departure has been automatically cancelled due to severe weather conditions.</p>
                </div>

                <p>At FerryCast, your safety is our top priority. Our marine intelligence system has flagged your route as hazardous for travel (e.g., high waves or strong winds).</p>
                
                <div class="details-box">
                    <h3 class="details-title">Trip Details</h3>
                    <div class="details-row">
                        <span class="label">Reference</span>
                        <span class="value">{{ $booking->booking_reference }}</span>
                    </div>
                    <div class="details-row">
                        <span class="label">Route</span>
                        <span class="value">{{ $schedule->origin->name }} &rarr; {{ $schedule->destination->name }}</span>
                    </div>
                    <div class="details-row">
                        <span class="label">Departure</span>
                        <span class="value">{{ $schedule->departure_time->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="details-row">
                        <span class="label">Tickets</span>
                        <span class="value">{{ $booking->quantity }}</span>
                    </div>
                </div>

                <div class="refund-info">
                    <strong>Refund Processed</strong><br><br>
                    Your payment of <strong>{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</strong> has been fully refunded. Depending on your bank, it may take 5-10 business days for the funds to appear in your account.
                </div>

                <div class="btn-container">
                    <a href="{{ url('/') }}" class="btn">Book a Rescheduled Trip</a>
                </div>
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} FerryCast. Safe travels.</p>
                <p>If you have any questions, please reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
