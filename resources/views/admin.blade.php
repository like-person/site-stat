<!DOCTYPE html>
<html>
    <head>
        <title>Админская панель</title>

        <link href="https://fonts.googleapis.com/css?family=Roboto:100,400,700&subset=cyrillic" rel="stylesheet" type="text/css">

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Roboto', serif;
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 96px;
            }
            .menu {
                font-size: 36px;
                margin-bottom: 30px;
                padding: 20px;
                border:2px solid #000;
            }
            .menu a {
                font-size: 36px;
            }
            .text {
                font-size: 26px;
                font-weight: normal;
            }
            .block {
                width: 24%;
                height: 500px;
                overflow: auto;
                margin-right: 1%;
                float: left;
            }
            .clear {clear: both;}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">Админская панель</div>
                <div class="menu">Статистика по: <?=$menu ?></div>
                <div class="text"><?=$content ?></div>
                <div class="clear"></div>
                <div class="text"><a href="/site/page0">Вернуться к сайту</a></div>
            </div>
        </div>
    </body>
</html>
