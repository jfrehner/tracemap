<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Tracemap</title>

        <link rel="stylesheet" href="./css/normalize.css">
        <link rel="stylesheet" href="./css/style.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script src="js/app.js"></script>
        <script src="//d3js.org/d3.v3.min.js" charset="utf-8"></script>
    </head>
    <body id="view-stats">

        <nav>
            <ul>
                <li>
                    <a href="./"><i class="fa fa-search"></i>Tracemap</a>
                </li>
                <li class="selected">
                    <a href="./stats"><i class="fa fa-bar-chart"></i>Stats</a>
                </li>
                <li>
                    <a href="./about"><i class="fa fa-info"></i>About</a>
                </li>
            </ul>
        </nav>

        <header>
            <hgroup>
                <h1>Tracemap</h1>
                <h2>We traced <span class="numberOfTraces"></span> URLs over <span class="numberOfHops"></span> Hops.</h2>
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
                        <td>Stat</td>
                        <td>Value</td>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Number of traced URLs</td>
                            <td class="numberOfTraces"></td>
                        </tr>
                        <tr>
                            <td>Number of Hops</td>
                            <td class="numberOfHops"></td>
                        </tr>
                        <tr>
                            <td>Average time to destination</td>
                            <td class="averageHopTime"></td>
                        </tr>
                    </tbody>
                </table>
                <h2>
                    The ten most traced URLs are
                </h2>
                <div id="topTenChart"></div>
                <table>
                    <thead>
                        <td>URL</td>
                        <td>Amounts traced</td>
                    </thead>
                    <tbody id="topTraces">
                    </tbody>
                </table>
            </section>

        </main>

        <footer>
            Copyright by
        </footer>

    </body>
</html>
