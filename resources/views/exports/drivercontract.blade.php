<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عقد عمل حر</title>
    <style>
        /* Embed Noto Naskh Arabic from public/fonts/noto */
        @font-face {
            font-family: 'Noto Naskh Arabic';
            src: url('/fonts/noto/NotoNaskhArabic-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Noto Naskh Arabic';
            src: url('/fonts/noto/NotoNaskhArabic-Bold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        body {
            font-family: 'Noto Naskh Arabic', 'DejaVu Sans', sans-serif;
            direction: rtl;
            unicode-bidi: embed;
            text-align: right;
            padding: 40px;
            line-height: 1.8;
            font-size: 14px;
        }
        
        .header {
            margin-bottom: 40px;
        }
        
        .logo {
            background-color: #FFFF00;
            display: inline-block;
            padding: 10px 20px;
            margin-bottom: 10px;
        }
        
        .logo h1 {
            color: #FF0000;
            font-size: 48px;
            margin: 0;
            font-weight: bold;
        }
        
        .subtitle {
            color: #000;
            font-weight: bold;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .underline {
            border-bottom: 2px solid #FFD700;
            display: inline-block;
            width: 200px;
            margin: 0 10px;
        }
        
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 40px 0;
        }
        
        .content {
            text-align: justify;
        }
        
        .section {
            margin: 15px 0;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .filled-text {
            text-decoration: underline;
            font-weight: bold;
            color: #000;
        }
        
        .signature-section {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
        }
        
        ul {
            list-style-type: disc;
            margin-right: 30px;
            direction: rtl;
            unicode-bidi: embed;
            text-align: right;
        }
        
        li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1>Anderson</h1>
        </div>
        <div class="subtitle">
            <span class="underline"></span> <span dir="ltr">National Express</span> <span class="underline"></span>
        </div>
    </div>
    
    <div class="title">
        تعهد بتقديم خدمات، تصنيف عمل حر
    </div>
    
    <div class="content">
        <div class="section">
            <p>
                بين الشركة ، <span class="bold">Anderson National Express ،</span> <span class="bold">SPA Transport de marchandises</span> ، التي يقع مقرها 
                الجزائر تحت رقم <span class="bold">99 ب -0007650- 16/00</span> ، يمثلها السيد <span class="bold">OUADAH OMAR</span>
            </p>
            <p style="text-align: center;">و السيد، <span class="filled-text"> Merzak Djnidi</span></p>
        </div>
        
        <div class="section">
            <p>أنا، الموقع أدناه، السيد، <span class="filled-text">{{ $driver_name }}</span></p>
        </div>
        
        <div class="section">
            <p>
                المولود في<span class="filled-text">{{ $birth_date }}</span> 
                <span class="filled-text">{{ $birth_place }}</span> و المقيم ب <span class="filled-text">{{ $address }}</span>
            </p>
        </div>
        
        <div class="section">
            <p>
                حامل وثيقة الهوية و / أو رخصة القيادة رقم <span class="filled-text">{{ $license_number }}</span>
            </p>
        </div>
        
        <div class="section">
            <p class="bold">
                أوافق على أداء الخدمات التالية كمستخدم (عامل توصيل) حر، لصالح شركة Anderson National Express.
            </p>
        
        <div class="section">
            <ul>
                <li>
                    أتعهد باستلام الطرود وأن أكون مسؤولاً عنها بصفة الوديعة أمانة فيما يتعلق بالكميات والجودة (تلف، ضياع، 
                    إلخ)، والمغادل نقداً وما يوافقه حسب القسائم والبيانات.
                </li>
                <li>
                    أتعهد بتسليم الطرود في جميع أنحاء إقليم ولاية <span class="filled-text"> </span> مقابل أجر <span class="filled-text">{{ $commission}}</span> دينار عن كل 
                    طرد يتم تسليمه
                </li>
                <li>
                    أتعهد بتحصيل المبالغ الموضحة في قسائم الشحن و / أو الملصقات.
                </li>
                <li>
                    أتعهد بإعادة المبالغ المحصلة من العملاء إلى شركة Anderson.
                </li>
                <li>
                    أواظب على إعادة الطرود التي لم يتم تسليمها إلى أندرسون في الآجال المتفق عليها.
                </li>
                <li>
                    هاتفي<span class="filled-text">...............................................................................</span>
                </li>
            </ul>
        </div>
        
        <div class="section" style="text-align: center; margin-top: 30px;">
            <p class="bold">حرر هذا الالتزام للتأكيد والخدمة فقط عن طريق الحق.</p>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <p>تاريخ<span class="filled-text">{{ $contract_date }}</span></p>
            </div>
            <div class="signature-box">
                <p>الامضاء+ بصمة الاصبع اليسار:</p>
            </div>
        </div>
    </div>
</body>
</html>