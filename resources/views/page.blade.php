<!DOCTYPE html>
<html>
    <head>
        <title>Сайт. Страницы</title>

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
            
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">ВЕБ-сайт</div>
                <div class="menu"><?=$menu ?></div>
                <div class="text"><?=$content ?></div>
                <div class="text"><a href="/admin/allsite">Просмотр статистики (админская панель)</a></div>
            </div>
        </div>
    </body>
</html>
