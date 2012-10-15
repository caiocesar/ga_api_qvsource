<?php

require_once 'google-api-php-client/src/apiClient.php';
require_once 'google-api-php-client/src/contrib/apiAnalyticsService.php';

session_start();

$client = new apiClient();
$client->setApplicationName('Hello Analytics API Sample');

// Visit //code.google.com/apis/console?api=analytics to generate your
// client id, client secret, and to register your redirect uri.
$client->setClientId('200692483436.apps.googleusercontent.com');
$client->setClientSecret('59ToYW6qu1yEpaMji-mxVNA2');
$client->setRedirectUri('http://www.zerocaio.com.br/HelloAnalyticsApi.php');
$client->setDeveloperKey('AIzaSyBnitmbUJDp8zlNi7FtLFirIo-798P92BA');
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));

// Magic. Returns objects from the Analytics Service instead of associative arrays.
$client->setUseObjects(true);

if (isset($_GET['code'])) {
  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if (!$client->getAccessToken()) {
  $authUrl = $client->createAuthUrl();
  print "<a class='login' href='$authUrl'>Connect Me!</a>";

} else {
  // Create analytics service object. See next step below.
  $analytics = new apiAnalyticsService($client);
  runMainDemo($analytics);
}

function runMainDemo(&$analytics) {
  try {

    // Step 2. Get the user's first profile ID.
    $profileId = getFirstProfileId($analytics);

    if (isset($profileId)) {

      // Step 3. Query the Core Reporting API.
      $results = getResults($analytics, $profileId);

      // Step 4. Output the results.
      printResults($results);
    }

  } catch (apiServiceException $e) {
    // Error from the API.
    print 'There was an API error : ' . $e->getCode() . ' : ' . $e->getMessage();

  } catch (Exception $e) {
    print 'There wan a general error : ' . $e->getMessage();
  }
}

function getFirstprofileId(&$analytics) {
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    $webproperties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

    if (count($webproperties->getItems()) > 0) {
      $items = $webproperties->getItems();
      $firstWebpropertyId = $items[0]->getId();

      $profiles = $analytics->management_profiles
          ->listManagementProfiles($firstAccountId, $firstWebpropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();
        return $items[0]->getId();

      } else {
        throw new Exception('No profiles found for this user.');
      }
    } else {
      throw new Exception('No webproperties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getResults(&$analytics, $profileId) {
	$optParams = array(
			'dimensions' => 'ga:source,ga:keyword',
			'sort' => '-ga:visits,ga:keyword',
			'filters' => 'ga:medium==organic',
			'max-results' => '25');
   return $analytics->data_ga->get(
       'ga:' . $profileId,
       '2012-09-01',
       '2012-09-30',
       'ga:visits,ga:pageviews',
	   $optParams);
}

function printResults(&$results) {
  if (count($results->getRows()) > 0) {
    $profileName = $results->getProfileInfo()->getProfileName();
    $rows = $results->getRows();
	echo imprimir_valores($results);
	echo imprimir_valores_csv($results);
//    $visits = $rows[0][0];

//    print "<p>First profile found: $profileName</p>";
//    print "<p>Total visits: $visits</p>";
  } else {
    print '<p>No results found.</p>';
  }
}

 function imprimir_valores($results) {
    $table = '<h3>Rows Of Data</h3>';

    if (count($results->getRows()) > 0) {
      $table .= '<table>';

      // Print headers.
      $table .= '<tr>';

      foreach ($results->getColumnHeaders() as $header) {
        $table .= '<th>' . $header->name . '</th>';
      }
      $table .= '</tr>';

      // Print table rows.
      foreach ($results->getRows() as $row) {
        $table .= '<tr>';
          foreach ($row as $cell) {
            $table .= '<td>'
                   . htmlspecialchars($cell, ENT_NOQUOTES)
                   . '</td>';
          }
        $table .= '</tr>';
      }
      $table .= '</table>';

    } else {
      $table .= '<p>No results found.</p>';
    }

    return $table;
 }

 function imprimir_valores_csv($results) {
    $table = '';

    if (count($results->getRows()) > 0) {
      //$table .= '<table>';

      // Print headers.
      //$table .= '<tr>';

      foreach ($results->getColumnHeaders() as $header) {
        $table .= $header->name . ',';
      }
      $table .= "\n";

      // Print table rows.
      foreach ($results->getRows() as $row) {
        
          foreach ($row as $cell) {
            $table .= htmlspecialchars($cell, ENT_NOQUOTES)
                   . ',';
          }
        $table .= "\n";
      }
      //$table .= '</table>';

    } else {
      $table .= '<p>No results found.</p>';
    }
	

	// Abre ou cria o arquivo bloco1.txt
	$fp = fopen("teste_csv.csv", "w");

	fwrite($fp, $table);

	fclose($fp);

	sobe_no_ftp();
	
    return $table;
 }
 
function sobe_no_ftp(){ 

	// Dados do servidor
	$servidor = 'ftpmulti.aunica.com'; // Endereço
	$usuario = 'qvftp'; // Usuário
	$senha = 'qvftp@2012'; // Senha

	// Abre a conexão com o servidor FTP
	$ftp = ftp_connect($servidor); // Retorno: true ou false

	// Faz o login no servidor FTP
	$login = ftp_login($ftp, $usuario, $senha); // Retorno: true ou false

	// Define variáveis para o envio de arquivo
	$local_arquivo = 'teste_csv.csv'; // Localização (local)
	$ftp_pasta = '/fiat/'; // Pasta (externa)
	$ftp_arquivo = 'teste_csv.csv'; // Nome do arquivo (externo)

	// Envia o arquivo pelo FTP em modo ASCII
	$envia = ftp_put($ftp, $ftp_pasta.$ftp_arquivo, $local_arquivo, FTP_BINARY); // Retorno: true / false

	// Encerra a conexão ftp
	ftp_close($ftp);

}
 
?>