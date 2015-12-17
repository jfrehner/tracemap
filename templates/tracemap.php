<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title><?php echo $this->data['page_title']; ?></title>

        <link rel="stylesheet" href="./css/normalize.css">
        <link rel="stylesheet" href="./css/style.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="./js/app.js"></script>
    </head>
    <body id="view-tracemap">

        <nav>
            <ul>
                <li class="selected">
                    <a href="./"><i class="fa fa-search"></i>Tracemap</a>
                </li>
                <li>
                    <a href="./stats"><i class="fa fa-bar-chart"></i>Stats</a>
                </li>
                <li>
                    <a href="./about"><i class="fa fa-info"></i>About</a>
                </li>
            </ul>
        </nav>

        <header>
            <hgroup>
                <h1><?php echo $this->data['page_title']; ?></h1>
                <h2>We traced {{ 100 }} Routes so far.</h2>
            </hgroup>
        </header>

        <main>

            <section id="tm-search">
                <form class="tm-search-form">
                    <input type="text" placeholder="Destination URL">
                    <button id="submitBtn">Trace it!</button>
                </form>
            </section>

            <section id="tm-google-map">
                <figure>
                    {{ Insert Google Map here }}
                </figure>
            </section>

        </main>

        <footer>
            Copyright by
        </footer>

    </body>
</html>
