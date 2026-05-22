<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: white; }
    .wrap {
        text-align: center;
        line-height: 0;
        padding: 3px 0;
    }
    img {
        max-width: 300px;
        width: 100%;
        height: auto;
        max-height: 50px;
        object-fit: contain;
        display: inline-block;
    }
</style>
</head>
<body>
<div class="wrap">
    <img src="file:///{{ str_replace('\\', '/', public_path('images/footer.png')) }}" alt="Footer">
</div>
</body>
</html>
