<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Tracemap</title>

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="js/app.js"></script>
    </head>
    <body id="view-stats">

        <nav>
            <ul>
                <li>
                    <a href="./index.php"><i class="fa"></i>Tracemap</a>
                </li>
                <li class="selected">
                    <a href="#"><i class="fa"></i>Stats</a>
                </li>
                <li>
                    <a href="./about.php"><i class="fa"></i>About</a>
                </li>
            </ul>
        </nav>

        <header>
            <hgroup>
                <h1>Tracemap</h1>
                <h2>We traced {{ 100 }} URLs over {{ 435 }} Hops.</h2>
            </hgroup>
        </header>

        <main>

            <section id="tm-google-map-stats">
                <h3>The ten most traced routes are:</h3>
                <figure>
                    {{ Insert Google Map here }}
                </figure>
            </section>

            <section id="tm-stats">
                <h3>
                    General Statistics
                </h3>
                <table>
                    <thead>
                        <td>
                            Stat
                        </td>
                        <td>
                            Value
                        </td>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Number of Hops
                            </td>
                            <td>
                                993
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Number of traced URLs
                            </td>
                            <td>
                                100
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Average time to destination
                            </td>
                            <td>
                                0.4s
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h3>
                    The ten most traced URLs are
                </h3>
                <table>
                    <thead>
                        <td>URL</td>
                        <td>Amounts traced</td>
                    </thead>
                    <tbody>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>www.google.ch</td>
                            <td>20</td>
                        </tr>
                    </tbody>
                </table>
            </section>

        </main>

        <footer>
            Copyright by
        </footer>

    </body>
</html>
