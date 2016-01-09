<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title><?php echo $this->data['page_title']; ?></title>

        <link rel="stylesheet" href="./css/normalize.css">
        <link rel="stylesheet" href="./css/style.css">
        <link rel="stylesheet" href="./css/flags.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
        <script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?v=3"></script>
        <script type="text/javascript" src="./js/app.js"></script>
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
                <h2>We traced <span class="numberOfTraces"></span> Routes so far.</h2>
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
                <div id="tm-map-initial"></div>
            </section>

            <section id="tm-data-raw">
                <h2></h2>
                <ul>

                </ul>
            </section>

            <section id="tm-data">
                <h2></h2>
                <p></p>
                <table id="traceroute-table">
                  <thead>
                    <tr>
                      <td>Country</td><td>Hop-Nr</td><td>Host</td><td>Ip-Address</td><td>1st Response</td><td>2nd Response</td><td>3rd Response</td>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
            </section>

        </main>

        <footer>
            &copy 2016 by Andreas Gassmann &amp Jonas Frehner
        </footer>

    </body>
</html>
