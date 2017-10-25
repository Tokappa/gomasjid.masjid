<?php
// Routes
namespace App;


use PDO;
use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;


$app->get('/', function ($request, $response, $args) use($db, $api_token) {
    // Sample log message
    // $this->logger->info("Solusi Masjid '/' route");

    // Check if this particular device is already installed
    // If not installed yet, display installation screen
    //
    //
    // TO BE DEVELOPED
    //
    //
    // $sth = $db->query('SELECT * FROM setting WHERE id="1";');
    // $row = $sth->fetchAll(PDO::FETCH_CLASS);
    // $setting = $row[0];

    // // Not installed yet
    if ($api_token == "")
    {
        // Render installation view
        return $this->renderer->render($response, 'install.phtml', $args);
    }
    else
    {
        // Render index view
        return $this->renderer->render($response, 'index.phtml', $args);
    }

});


$app->get('/old', function ($request, $response, $args) use($db, $api_token) {
    return $this->renderer->render($response, 'index.old.phtml', $args);
});



// Redirect to main page
$app->get('/install', function ($req, $res, $args) {
    return $res->withStatus(302)->withHeader('Location', '/');
});



// Send usage statistic
$app->get('/device-stats', function ($request, $response, $args) use ($db, $api_token) {

    $app_setting = $this->settings['GoMasjid'];
    $api_url = $app_setting['api_url'];


    // Calculate disk size
    $total_space        = trim(shell_exec('df -h | tail -n +2 | grep /dev/root | awk \'{print $2}\'')) . "B";
    $used_space         = trim(shell_exec('df -h | tail -n +2 | grep /dev/root | awk \'{print $3}\'')) . "B";
    $free_space         = trim(shell_exec('df -h | tail -n +2 | grep /dev/root | awk \'{print $4}\'')) . "B";
    $used_space_perc    = trim(shell_exec("df -h | tail -n +2 | grep /dev/root | awk '{print $5}'"));

    // Get temperature info
    $info = shell_exec('sensors');
    preg_match('/:\s+([+|-].*?)\s*?\(/', $info, $m);
    $info = $m[1];
    if (!$info) {
        // Get temp from thermal
        $info = shell_exec('cat /sys/class/thermal/thermal_zone0/temp');
        $info = $info / 1000;
    }
    if (!$info) {
        $temperature = "N/A";
    } else {
        $temperature = trim($info);
    }
    echo $temperature;


    // Get bandwidth info
    $info = shell_exec("cat /proc/net/dev | grep wlan0 | awk '{print ($2 + $10)}'");

    if (($info / pow(1024, 2)) > pow(1024, 2))
    {
        $bandwidth = round($info / pow(1024, 3)) . " GiB";
    }
    else
    {
        $bandwidth = round($info / pow(1024, 2)) . " MiB";
    }

    $status = array("total_space" => $total_space,
                                "used_space" => $used_space,
                                "free_space" => $free_space,
                                "used_space_perc" => $used_space_perc,
                                "temperature" => $temperature,
                                "bandwidth" => $bandwidth);
    // print_r($status);

    /*
    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/device-status';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            'json' => ['status' => $status],
            'http_errors' => false
            ]);

        var_dump($response->getBody()->getContents());

    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }
    */

    // echo $bandwidth;

});



$app->get('/slideshow', function ($request, $response, $args) use ($db) {
    $app_setting        = $this->settings['GoMasjid'];
    $image_location     = $app_setting['image_dir'];
    $album_location     = $app_setting['album_dir'];

    //
    // $events_file_location = $app_setting['events_file'];
    // $handle = fopen($events_file_location, "r");
    // $events = json_decode(fread($handle, filesize($events_file_location)));
    // fclose($events_file_location);
    //

    // General Setting
    $sth = $db->query('SELECT * FROM setting WHERE id="1";');
    $row = $sth->fetchAll(PDO::FETCH_CLASS);
    $setting = $row[0];

    // Setting default value
    $events = $news = $financial = $info_jumat = null;

    // Events
    $sth = $db->query('SELECT * FROM slideshows;');
    if ($sth)
    {
        $events = $sth->fetchAll(PDO::FETCH_CLASS);
    }

    // Albums
    $sth = $db->query('SELECT * FROM albums ORDER BY album_id ASC, sequence ASC;');
    if ($sth)
    {
        $albums = $sth->fetchAll(PDO::FETCH_CLASS);
    }

    // News
    $sth = $db->query('SELECT * FROM news;');
    if ($sth)
    {
        $news = $sth->fetchAll(PDO::FETCH_CLASS);
    }

    // Financial
    $sth = $db->query('SELECT * FROM financial WHERE id="1";');
    if ($sth)
    {
        $row = $sth->fetchAll(PDO::FETCH_CLASS);
        $financial = $row[0];
    }

    // Info Jumat
    $sth = $db->query('SELECT * FROM jumat;');
    if ($sth)
    {
        $row = $sth->fetchAll(PDO::FETCH_CLASS);
        $info_jumat = $row[0];
    }


    return $this->renderer->render($response, 'slideshow.phtml',
        [
            'waktu_sholat'  => WaktuShalat($setting),
            'profil'        => $setting,
            'events'        => $events,
            'albums'        => $albums,
            'image_location' => $image_location,
            'album_location' => $album_location,
            'rolling_text'  => $news,
            'financial'     => $financial,
            'info_jumat'    => $info_jumat
        ]);
});

$app->get('/adzan-countdown', function ($request, $response, $args) use ($db) {
    $app_setting = $this->settings['GoMasjid'];

    $sth = $db->query('SELECT * FROM setting WHERE id="1";');
    $row = $sth->fetchAll(PDO::FETCH_CLASS);
    $setting = $row[0];

    return $this->renderer->render($response, 'adzan-countdown.phtml',
        [
            'waktu_sholat' => WaktuShalat($setting),
            'profil' => $setting,
        ]);
});

$app->get('/adzan', function ($request, $response, $args) {
    return $this->renderer->render($response, 'adzan.phtml', $args);
});

$app->get('/iqamat-countdown', function ($request, $response, $args) use ($db) {
    $app_setting = $this->settings['GoMasjid'];

    $sth = $db->query('SELECT * FROM setting WHERE id="1";');
    $row = $sth->fetchAll(PDO::FETCH_CLASS);
    $setting = $row[0];

    return $this->renderer->render($response, 'iqamat-countdown.phtml',
        [
            'waktu_sholat' => WaktuShalat($setting),
            'profil' => $setting,
        ]);
});

$app->get('/sholat', function ($request, $response, $args) {
    return $this->renderer->render($response, 'clock-big.phtml', $args);
});

$app->get('/sholat-time', function ($request, $response, $args) use ($db) {
    $app_setting = $this->settings['GoMasjid'];

    $sth = $db->query('SELECT * FROM setting WHERE id="1";');
    $row = $sth->fetchAll(PDO::FETCH_CLASS);
    $setting = $row[0];

    $waktu_sholat = WaktuShalat($setting);
    // $response = $app->response();
    // $response['Content-Type'] = 'application/json';
    // $response['X-Powered-By'] = 'Potato Energy';
    // $response->status(200);
    // etc.
    $fine_tune = json_decode($setting->fine_tune);
    $nama_sholat_waktu[] = ['name' => 'subuh', 'time' => date("Y-m-d") .' '. $waktu_sholat[0], 'fine_tune' => ($fine_tune->subuh) ? $fine_tune->subuh : null];
    $nama_sholat_waktu[] = ['name' => 'dzuhur', 'time' => date("Y-m-d") .' '. $waktu_sholat[2], 'fine_tune' => ($fine_tune->dzuhur) ? $fine_tune->dzuhur : null];
    $nama_sholat_waktu[] = ['name' => 'ashar', 'time' => date("Y-m-d") .' '. $waktu_sholat[3], 'fine_tune' => ($fine_tune->ashar) ? $fine_tune->ashar : null];
    $nama_sholat_waktu[] = ['name' => 'maghrib', 'time' => date("Y-m-d") .' '. $waktu_sholat[4], 'fine_tune' => ($fine_tune->maghrib) ? $fine_tune->maghrib : null];
    $nama_sholat_waktu[] = ['name' => 'isya', 'time' => date("Y-m-d") .' '. $waktu_sholat[5], 'fine_tune' => ($fine_tune->isya) ? $fine_tune->isya : null];
    $body = $response->getBody();
    $body->write(json_encode($nama_sholat_waktu));
    return $response;

});








////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
//
// Synchronization process
//
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$app->get('/download-configuration', function ($request, $response, $args) use ($db, $api_token){

    $app_setting = $this->settings['GoMasjid'];
    $api_url = $app_setting['api_url'];

    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/config';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            'http_errors' => false
            ]);

        // print_r($response->getBody());

        $response_json = json_decode($response->getBody()->getContents());

        // print_r($response_json);

        $sth = $db->prepare('UPDATE setting SET
                                        name = ?,
                                        avatar = ?,
                                        phone = ?,
                                        address = ?,
                                        convention = ?,
                                        fine_tune = ?,
                                        lat = ?,
                                        lng = ?,
                                        alt = ?,
                                        updated_at = ?
                                    WHERE id = "1";');
        $sth->execute([
            $response_json->name,
            "",
            $response_json->phone,
            $response_json->address,
            $response_json->convention,
            $response_json->fine_tune,
            $response_json->lat,
            $response_json->lng,
            $response_json->alt,
            $response_json->updated_at
        ]);
        // echo $response_json;
        return $response_json;
    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }


});




$app->get('/download-slideshows', function ($req, $res, $args) use ($db, $api_token) {

    $app_setting    = $this->settings['GoMasjid'];
    $api_url        = $app_setting['api_url'];


    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/slideshows';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            ]);

        $event_images = array();

        $response_json = json_decode($response->getBody()->getContents());

        // Delete first
        $db->exec('DELETE FROM slideshows;');
        $db->exec('VACUUM');
        $db->exec('DELETE FROM sqlite_sequence WHERE name="slideshows";');

        // Then Insert
        foreach ($response_json as $event)
        {
            $sth = $db->prepare('INSERT INTO slideshows (
                                            image,
                                            title,
                                            md5,
                                            updated_at
                                            )
                                        VALUES (
                                            ?,
                                            ?,
                                            ?,
                                            ?
                                        );');

            $sth->execute([
                basename($event->gallery->image_url),
                $event->gallery->title,
                $event->gallery->md5,
                $event->gallery->updated_at
            ]);

            $event_images[] = array(
                'md5' => $event->gallery->md5,
                'image' => $event->gallery->image_url,
            );

        }

        // Download images
        // But need to check if we've ever download this image before (later)
        $image_location = $app_setting['image_dir'];

        $stripped_images = array();

        // var_dump($event_images);
        foreach ($event_images as $image)
        {
            $filename = basename($image['image']);

            // If image not exists yet
            if ( ! file_exists($image_location.$filename))
            {
                // The file doesn't exists
                $image_file     = fopen($image_location.$filename, "w+");

                echo "Note: File (".$filename.") not exists, downloading\n\n";
                // Download image
                $the_image      = $guzzle->request('GET', $image['image'], [[], 'sink' => $image_file]);
                fclose($image_file);
            }

            // If exsists, check md5sum if different
            else if ( md5_file($image_location.$filename) != $image['md5'])
            {
                // The file doesn't exists
                $image_file     = fopen($image_location.$filename, "w+");

                echo "Note: Checksum of (".$filename.") different, downloading again\n\n";
                $the_image      = $guzzle->request('GET', $image['image'], [[], 'sink' => $image_file]);
                fclose($image_file);
            }

            // Add to stripped_images array (deletion task)
            $stripped_images[] = $filename;

            // The file is valid, do nothing

        }

        // Remove unnecessary images
        $files          = glob($image_location.'*'); // get all file names
        foreach($files as $file){ // iterate files
            if (is_file($file) && (! in_array(basename($file), $stripped_images)))
            {
                if ($file != "index.php")
                {
                    echo $file;
                    echo " deleted<br>";
                    unlink($file); // delete file
                }
            }
        }

        return $response_json;
    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }

});




$app->get('/download-albums', function ($req, $res, $args) use ($db, $api_token) {

    $app_setting    = $this->settings['GoMasjid'];
    $api_url        = $app_setting['api_url'];


    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/albums';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            ]);

        $event_images = array();

        $response_json = json_decode($response->getBody()->getContents());

        // Delete first
        $db->exec('DELETE FROM albums;');
        $db->exec('VACUUM');
        $db->exec('DELETE FROM sqlite_sequence WHERE name="albums";');

        // print_r($response_json);
        // exit();

        // Then Insert
        foreach ($response_json as $event)
        {
            $album_id = $event->album->hashed_id;
            $album_title = $event->album->title;

            // print_r($event);

            foreach ($event->album->images as $album_image)
            {

                $sth = $db->prepare('INSERT INTO albums (
                                                album_id,
                                                album_title,
                                                image,
                                                title,
                                                sequence,
                                                md5,
                                                updated_at
                                                )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            );');

                $sth->execute([
                    $album_id,
                    $album_title,
                    basename($album_image->image_url),
                    $album_image->title,
                    $album_image->sequence,
                    $album_image->md5,
                    $album_image->updated_at
                ]);

                $event_images[] = array(
                    'md5'       => $album_image->md5,
                    'image'     => $album_image->image_url,
                );
            }

        }

        // Download images
        // But need to check if we've ever download this image before (later)
        $image_location = $app_setting['album_dir'];

        // print_r($event_images);
        // exit();

        $stripped_images = array();

        // var_dump($event_images);
        foreach ($event_images as $image)
        {
            $filename = basename($image['image']);

            // If image not exists yet
            if ( ! file_exists($image_location.$filename))
            {
                // The file doesn't exists
                $image_file     = fopen($image_location.$filename, "w+");

                echo "Note: File (".$filename.") not exists, downloading\n\n";
                // Download image
                $the_image      = $guzzle->request('GET', $image['image'], [[], 'sink' => $image_file]);
                fclose($image_file);
            }

            // If exsists, check md5sum if different
            else if ( md5_file($image_location.$filename) != $image['md5'])
            {
                // The file doesn't exists
                $image_file     = fopen($image_location.$filename, "w+");

                echo "Note: Checksum of (".$filename.") different, downloading again\n\n";
                $the_image      = $guzzle->request('GET', $image['image'], [[], 'sink' => $image_file]);
                fclose($image_file);
            }

            // Add to stripped_images array (deletion task)
            $stripped_images[] = $filename;

            // The file is valid, do nothing

        }

        // Remove unnecessary images
        $files          = glob($image_location.'*'); // get all file names
        foreach($files as $file){ // iterate files
            if (is_file($file) && (! in_array(basename($file), $stripped_images)))
            {
                if ($file != "index.php")
                {
                    echo $file;
                    echo " deleted<br>";
                    unlink($file); // delete file
                }
            }
        }

        return $response_json;
    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }

});





$app->get('/download-financial', function ($req, $res, $args) use ($db, $api_token){

    $app_setting    = $this->settings['GoMasjid'];
    $api_url        = $app_setting['api_url'];

    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/financial';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            'http_errors' => false
            ]);

        $response_json = json_decode($response->getBody()->getContents());
        // var_dump($response_json);

        if ($response_json)
        {
            // Update
            $sth = $db->prepare('UPDATE financial SET
                                            income = ?,
                                            expense = ?,
                                            balance = ?,
                                            updated_at = ?
                                        WHERE id = "1";');
            $sth->execute([
                $response_json->income,
                $response_json->expense,
                $response_json->balance,
                $response_json->updated_at
            ]);
        }

        return $response_json;
    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }

});




$app->get('/download-jumat', function ($req, $res, $args) use ($db, $api_token){

    $app_setting    = $this->settings['GoMasjid'];
    $api_url        = $app_setting['api_url'];

    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/jumat';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            'http_errors' => false
            ]);

        $response_json = json_decode($response->getBody()->getContents());
        // var_dump($response_json);

        if ($response_json)
        {
            // Update
            $sth = $db->prepare('UPDATE jumat SET
                                            muadzin = ?,
                                            khatib = ?,
                                            imam= ?,
                                            updated_at = ?
                                        WHERE id = "1";');
            $sth->execute([
                $response_json->muadzin,
                $response_json->khatib,
                $response_json->imam,
                $response_json->updated_at
            ]);
        }

        return $response_json;
    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }
});




$app->get('/download-news', function ($req, $res, $args) use ($db, $api_token) {

    $app_setting = $this->settings['GoMasjid'];
    $api_url = $app_setting['api_url'];

    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/news';

        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $api_token],
            'http_errors' => false
            ]);

        $response_json = json_decode($response->getBody()->getContents());

        // Delete first
        // DELETE FROM table_name
        // VACUUM
        $db->exec('DELETE FROM news;');
        $db->exec('VACUUM');
        $db->exec('DELETE FROM sqlite_sequence WHERE name="news";');

        if ($response_json)
        {
            // Then Insert
            foreach ($response_json as $news)
            {
                $sth = $db->prepare('INSERT INTO news (
                                                title,
                                                content,
                                                updated_at
                                                )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?
                                            );');
                $sth->execute([
                    $news->title,
                    $news->content,
                    $news->updated_at
                ]);
            }
        }


        return $response_json;
    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }




});



// Set API_KEY to database
$app->post('/install', function ($req, $res, $args) use ($db) {
    // Check if this API TOKEN is valid
    $app_setting = $this->settings['GoMasjid'];
    $api_url = $app_setting['api_url'];

    try {
        $guzzle = new Client([
            // Base URI is used with relative requests
            'base_uri' => $api_url,
            // You can set any number of default request options.
            // 'timeout'  => 2.0,
        ]);

        $url_path =  '/api/v1/check-token';

        $request_parameters = $req->getParsedBody();

        // var_dump($request_parameters["api_token"]);
        $response = $guzzle->request('POST', $url_path, [
            'headers' => ['Authorization' => 'Bearer ' . $request_parameters["api_token"]],
            // 'headers' => ['Authorization' => 'Bearer 0eBAL964MM4bDq0TUyqodBUBa9aZnFF9BC0zYtNDKqFWopt3eP8evxfPzkYC'],
            ]);

        $response_json = json_decode($response->getBody()->getContents());
        // var_dump($response_json);

        // Invalid API TOKEN
        if ( ! is_null($response_json))
        {
            // echo "VALID KEY!";
            $sth = $db->prepare('UPDATE setting SET
                                            api_token = ?
                                        WHERE id = "1";');
            $sth->execute([
                $response_json->api_token
            ]);

        }

        $api_token = $response_json->api_token;

        // return $res->withStatus(302)->withHeader('Location', '/');
        // Need to download all stuff for this masjid
        try {
            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => $api_url,
                // You can set any number of default request options.
                // 'timeout'  => 2.0,
            ]);

            // CONFIGURATION
            $url_path =  '/api/v1/config';

            $response = $client->request('POST', $url_path, [
                'headers' => ['Authorization' => 'Bearer ' . $api_token],
                'http_errors' => false
                ]);

            $response_json = json_decode($response->getBody()->getContents());

            $sth = $db->prepare('UPDATE setting SET
                                            nama = ?,
                                            avatar = ?,
                                            telepon = ?,
                                            alamat = ?,
                                            convention = ?,
                                            fine_tune = ?,
                                            lat = ?,
                                            lon = ?,
                                            alt = ?,
                                            updated_at = ?
                                        WHERE id = "1";');
            $sth->execute([
                $response_json->name,
                $response_json->avatar,
                $response_json->phone,
                $response_json->address,
                $response_json->convention,
                $response_json->fine_tune,
                $response_json->lat,
                $response_json->lon,
                $response_json->alt,
                $response_json->updated_at
            ]);

            // EVENTS
            $url_path =  '/api/v1/events';

            $response = $client->request('POST', $url_path, [
                'headers' => ['Authorization' => 'Bearer ' . $api_token],
                ]);

            $event_images = array();

            $response_json = json_decode($response->getBody()->getContents());

            // Insert
            foreach ($response_json as $event)
            {
                $sth = $db->prepare('INSERT INTO events (
                                                image,
                                                title,
                                                updated_at
                                                )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?
                                            );');
                $sth->execute([
                    $event->image,
                    $event->title,
                    $event->updated_at
                ]);

                $event_images[] = $event->image;

            }

            // Download images
            // But need to check if we've ever download this image before (later)

            // var_dump($event_images);
            foreach ($event_images as $image)
            {
                // var_dump($event);
            //     // Download image
                $filename = basename($image);
                $image_location = $app_setting['image_dir'];


                if ( ! file_exists($image_location.$filename))
                {
                    // The file doesn't exists
                    $image_file = fopen($image_location.$filename, "w+");
                    $image = $client->request('GET', $image, [[], 'sink' => $image_file]);
                    fclose($image_file);
                }

            //     // echo $image;
            }


            // FINANCIAL
            $url_path =  '/api/v1/financial';

            $response = $client->request('POST', $url_path, [
                'headers' => ['Authorization' => 'Bearer ' . $api_token],
                'http_errors' => false
                ]);

            $response_json = json_decode($response->getBody()->getContents());
            var_dump($response_json);

            // Update
            $sth = $db->prepare('UPDATE financial SET
                                            pemasukan = ?,
                                            pengeluaran = ?,
                                            saldo = ?,
                                            updated_at = ?
                                        WHERE id = "1";');
            $sth->execute([
                $response_json->pemasukan,
                $response_json->pengeluaran,
                $response_json->saldo,
                $response_json->updated_at
            ]);

            // JUMAT
            $url_path =  '/api/v1/jumat';

            $response = $client->request('POST', $url_path, [
                'headers' => ['Authorization' => 'Bearer ' . $api_token],
                'http_errors' => false
                ]);

            $response_json = json_decode($response->getBody()->getContents());
            var_dump($response_json);

            // Update
            $sth = $db->prepare('UPDATE jumat SET
                                            muadzin = ?,
                                            khatib = ?,
                                            imam= ?,
                                            updated_at = ?
                                        WHERE id = "1";');
            $sth->execute([
                $response_json->muadzin,
                $response_json->khatib,
                $response_json->imam,
                $response_json->updated_at
            ]);

            // NEWS
            $url_path =  '/api/v1/news';

            $response = $client->request('POST', $url_path, [
                'headers' => ['Authorization' => 'Bearer ' . $api_token],
                'http_errors' => false
                ]);

            $response_json = json_decode($response->getBody()->getContents());

            // Insert
            foreach ($response_json as $news)
            {
                $sth = $db->prepare('INSERT INTO news (
                                                title,
                                                content,
                                                updated_at
                                                )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?
                                            );');
                $sth->execute([
                    $news->title,
                    $news->content,
                    $news->updated_at
                ]);
            }


        }
        catch (RequestException $e) {
            echo 'Error: ' . $e->getMessage();
        }

    }
    catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage();
    }

});





////////////////////////////////////////////////////////////////////////////////
//
// function definitions
//
////////////////////////////////////////////////////////////////////////////////


function sign($x) {
    if($x==0)
    return 0;
    else
    return $x / abs($x);
}

function WaktuShalat($configuration = null) {
    $tanggal=getdate();
    $J=$tanggal['yday'];

    // EDIT..?!
    // echo $configuration->alt;
    $H = empty($configuration->alt) ? 50 : $configuration->alt;    // Ketinggian laut (meter)

    //
    //
    // Convention 										Fajr Angle 		Isha Angle
    // Muslim World League 								18 				17
    // Islamic Society of North America (ISNA) 			15 				15
    // Egyptian General Authority of Survey 			19.5 			17.5
    // Umm al-Qura University, Makkah 					18.5 			90 min after Maghrib - 120 min during Ramadan
    // University of Islamic Sciences, Karachi 			18 				18
    //
    //

    switch ($configuration->convention)
    {
        case "mwlg":
            $Gd = 18;
            $Gn = 17;
            break;

        case "isna":
            $Gd = 15;
            $Gn = 15;
            break;

        case "egas":
            $Gd = 19.5;
            $Gn = 17.5;
            break;

        case "uqum":
            $Gd = 18.5;
            $Gn = 90;
            break;

        case "uisk":
            $Gd = 18;
            $Gn = 18;
            break;

        default:
            $Gd = 18;    // Sudut Fajar Senja (15°-19°)  -  Dawn’s Twilight Angle (15°-19°)
            $Gn = 18;    // Sudut Malam Senja (15°-19°)  -  Night’s Twilight Angle (15°-19°)
    }

	// Default coordinates is Yogyakarta => -7.7893603,110.367609
    $B = is_null($configuration->lat) ? -7.7893603 : $configuration->lat;    // Garis Lintang (derajat)  -  Latitude (Degrees)
    $L = is_null($configuration->lng) ? 110.367609 : $configuration->lng;    // Garis Bujur (derajat)  -  Longitude (Degrees)
    $TZ = 7;    // Waktu Daerah (jam)  -  Time Zone (Hours)
    $Sh = 1;    // Sh=1 (Shafii) - Sh=2 (Hanafi)
    // STOP EDITING

    $D = 0;    // Turun mengenai matahari (derajat)  -  Solar Declination (derajat)
    $T = 0;    // Persamaan dari waktu (menit)  -  Equation of times (minutes)
    $R = 0;    // Referensi Garis Bujur (derajat)  -  Reference Longitude (Degrees)

    $beta = 2 * pi() * $J / 365;
    // $D = Sun Declination
    $D = (180 / pi()) * (0.006918 - (0.399912 * cos($beta)) + (0.070257 * sin($beta)) - (0.006758 * cos(2 * $beta)) + (0.000907 * sin(2 * $beta)) - (0.002697 * cos(3 * $beta)) + (0.001480 * sin(3 * $beta)));
    $T = 229.18 * (0.000075 + (0.001868 * cos($beta)) - (0.032077 * sin($beta)) - (0.014615 * cos(2 * $beta)) - (0.040849 * sin(2 * $beta)));
    $R = 15 * $TZ;
    $G = 18;
    $Z = 12 + (($R - $L) / 15) - ($T / 60);
    $U = (180 / (15 * pi())) * acos((sin((-0.8333 - 0.0347 * sign($H) * sqrt(abs($H))) * (pi() / 180)) - sin($D * (pi() / 180)) * sin($B * (pi() / 180))) / (cos($D * (pi() / 180)) * cos($B * (pi() / 180))));
    $Vd = (180 / (15 * pi())) * acos((-sin($Gd * (pi() / 180)) - sin($D * (pi() / 180)) * sin($B * (pi() / 180))) / (cos($D * (pi() / 180)) * cos($B * (pi() / 180))));
    if ($configuration->convention != 'uqum')
    {
        $Vn = (180 / (15 * pi())) * acos((-sin($Gn * (pi() / 180)) - sin($D * (pi() / 180)) * sin($B * (pi() / 180))) / (cos($D * (pi() / 180)) * cos($B * (pi() / 180))));
    }
    $W = (180 / (15 * pi())) * acos((sin(atan(1 / ($Sh + tan(abs($B - $D) * pi() / 180))))-sin($D * pi() / 180) * sin($B * pi() / 180)) / (cos($D * pi() / 180) * cos($B * pi() / 180)));

    $fine_tune = json_decode($configuration->fine_tune);

    for ($i=1; $i<= 6; $i++)
    {
        switch ($i) {
            // Subuh
            case 1:
                $hasil = $Z-$Vd;
                if ($fine_tune->subuh[1] != 0)
                {
                    $hasil = $hasil + ($fine_tune->subuh[1] / 60);
                }
                break;

            // Syuruq
            case 2:
                $hasil = $Z-$U;
                break;

            // Dzuhur
            case 3:
                $hasil = $Z;
                if ($fine_tune->dzuhur[1] != 0)
                {
                    $hasil = $hasil + ($fine_tune->dzuhur[1] / 60);
                }
                break;

            // Ashar
            case 4:
                $hasil = $Z+$W;
                if ($fine_tune->ashar[1] != 0)
                {
                    $hasil = $hasil + ($fine_tune->ashar[1] / 60);
                }
                break;

            // Maghrib
            case 5:
                $hasil = $Z+$U;
                if ($fine_tune->maghrib[1] != 0)
                {
                    $hasil = $hasil + ($fine_tune->maghrib[1] / 60);
                }
                break;
            // Isya
            case 6:
                if ($configuration->convention == 'uqum')
                {
                    $hasil = $Z+$U+1.5;
                }
                else
                {
                    $hasil = $Z+$Vn;
                }

                if ($fine_tune->isya[1] != 0)
                {
                    $hasil = $hasil + ($fine_tune->isya[1] / 60);
                }

                break;
        }


        $jam = floor($hasil);
        $menit = floor(($hasil - $jam) * 60);
        $detik = floor(((($hasil - $jam) * 60) - $menit) * 60);
        if (strlen($jam)==1) $jam="0" . $jam;
        if (strlen($menit)==1) $menit="0" . $menit;
        if (strlen($detik)==1) $detik="0" . $detik;
        $output[] = "$jam:$menit:$detik";
    }

    // Imsak
    /*
    if (isset($fine_tune->imsak))
    {
        $imsak = $fine_tune->imsak;
        if (is_array($imsak))
        {
            if ($imsak[0] == 1)
            {
                $hasil = $Z-$Vd;
                if ($fine_tune->subuh[1] != 0)
                {
                    $hasil = $hasil + (($fine_tune->subuh[1] - $imsak[1]) / 60);
                    $jam = floor($hasil);
                    $menit = floor(($hasil - $jam) * 60);
                    $detik = floor(((($hasil - $jam) * 60) - $menit) * 60);
                    if (strlen($jam)==1) $jam="0" . $jam;
                    if (strlen($menit)==1) $menit="0" . $menit;
                    if (strlen($detik)==1) $detik="0" . $detik;
                    $output[6] = "$jam:$menit:$detik";
                }
            }
        }
    }
    */
    // Always enable Imsak
    $imsak = array(1, 10);
    if (is_array($imsak))
    {
        if ($imsak[0] == 1)
        {
            $hasil = $Z-$Vd;
            if ($fine_tune->subuh[1] != 0)
            {
                $hasil = $hasil + (($fine_tune->subuh[1] - $imsak[1]) / 60);
                $jam = floor($hasil);
                $menit = floor(($hasil - $jam) * 60);
                $detik = floor(((($hasil - $jam) * 60) - $menit) * 60);
                if (strlen($jam)==1) $jam="0" . $jam;
                if (strlen($menit)==1) $menit="0" . $menit;
                if (strlen($detik)==1) $detik="0" . $detik;
                $output[6] = "$jam:$menit:$detik";
            }
        }
    }

    return $output;
}
