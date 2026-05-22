<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; background: white; }

    .wrap {
        padding: 6px 12.7mm 4px;
    }

    /* Table-based layout — wkhtmltopdf renders flexbox inconsistently */
    .header-row {
        display: table;
        width: 100%;
        table-layout: fixed;
    }
    .col { display: table-cell; vertical-align: middle; }
    .col-left  { width: 25%; text-align: right; padding-right: 8px; }
    .col-center { width: 50%; text-align: center; line-height: 1.4; }
    .col-right { width: 25%; text-align: left; padding-left: 8px; }

    img.logo { height: 70px; width: auto; }

    .sep-blue {
        height: 2px;
        background: #003399;
        margin: 5px 0 3px;
    }
    .sep-red {
        height: 4px;
        background: #cc0000;
    }
    .doc-title {
        text-align: center;
        font-size: 17px;
        font-weight: bold;
        margin-top: 6px;
        letter-spacing: 0.5px;
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="header-row">
        <div class="col col-left">
            <img class="logo"
                 src="file:///{{ str_replace('\\', '/', public_path('images/MTWD-Logo.png')) }}"
                 alt="MTWD Logo">
        </div>
        <div class="col col-center">
            <div style="font-size:12px; margin-bottom:1px;">Republic of the Philippines</div>
            <div style="font-size:13.5px; font-weight:bold; letter-spacing:0.2px;">METROPOLITAN TUGUEGARAO WATER DISTRICT</div>
            <div style="font-size:10px; color:#111;">Main Avenue, San Gabriel, Tuguegarao City</div>
            <div style="font-size:10px; color:#111;">Tel. No. (078) 844-1586; 844-7309; Telefax: (078) 844-9136</div>
            <div style="font-size:10px; color:#111;">Website: www.mtwd.gov.ph</div>
        </div>
        <div class="col col-right">
            <img class="logo"
                 src="file:///{{ str_replace('\\', '/', public_path('images/Bagong-Pilipinas-Logo.png')) }}"
                 alt="Bagong Pilipinas">
        </div>
    </div>
    <div class="sep-blue"></div>
    <div class="sep-red"></div>
    <div class="doc-title">SERVICE RECORD</div>
</div>
</body>
</html>
