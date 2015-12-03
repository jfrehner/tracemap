<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Tracemap</title>

        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="js/app.js"></script>
    </head>
    <body>

        <nav>
            <ul>
                <li>
                    Tracemap
                </li>
                <li>
                    Stats
                </li>
                <li>
                    About
                </li>
            </ul>
        </nav>

        <header>
            <hgroup>
                <h1>Tracemap</h1>
                <h2>We traced 100 Routes so far.</h2>
            </hgroup>
        </header>

        <main>

            <section>
                <form>
                    <input type="text" placeholder="Destination URL">
                    <button>Trace it!</button>
                </form>
            </section>

            <section>
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
