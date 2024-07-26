<?php

namespace App\Controllers;

use App\Models\GeneralModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Google\Client as Google_Client;
use Google\Service\Indexing as Google_Service_Indexing;
use GuzzleHttp\Client as GuzzleHttpClient;

class SeoController extends BaseController
{
    protected $generalModel;

    public function __construct()
    {
        $this->generalModel = new GeneralModel();
    }

    public function index()
    {
        if (!session()->has('user_name')) {
            return redirect()->to('/login')->with('msg', 'Login required to access this page. Please log in.');
        }

        $this->generalModel->setTable('websites');
        $data['allwebsites'] = $this->generalModel->getAll();
        $data['page_title'] = 'SEO Dashboard';

        return view('seo_dashboard', $data);
    }
    //Method for manual enter the url
    public function pingWebsite()
    {
        // Load the Google API Client Library
        require_once APPPATH . 'google-api-php-client/vendor/autoload.php';

        // Set up the Google API client
        $client = new Google_Client();
        $client->setApplicationName('My Indexing API');
        $client->setAuthConfig(APPPATH . '/config/futuristic-index-6241a8802f5a.json');
        $client->setScopes(['https://www.googleapis.com/auth/indexing']);

        // Create the Indexing API client   
        $indexing_service = new Google_Service_Indexing($client);

        // Get a Guzzle HTTP Client (assuming you have Guzzle installed via Composer)
        $httpClient = new GuzzleHttpClient(['base_uri' => 'https://indexing.googleapis.com']);

        // Endpoint for URL Notifications
        $endpoint = '/v3/urlNotifications:publish';

        // Check if the URL parameter is set in the POST request
        $url = $this->request->getPost('url');
        // Initialize messages array
        $messages = [];

        if (!$url) {
            $messages[] = 'Error: URL parameter is missing.';
        }

        // Define contents here. Ensure JSON formatting and proper escaping.
        $content = json_encode([
            'url' => $url,
            'type' => 'URL_UPDATED'
        ]);

        // Set headers and make the request
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $client->fetchAccessTokenWithAssertion()['access_token'],
        ];

        try {
            $response = $httpClient->post($endpoint, [
                'headers' => $headers,
                'body' => $content,
            ]);

            $statusCode = $response->getStatusCode();
            

            if ($statusCode === 200) {
                $messages[] = 'Successfully pinged URL: ' . $url;
            } else {
                $messages[] = 'Error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
            }
        } catch (\Exception $e) {
            $messages[] = 'Exception: ' . $e->getMessage();
            // Handle exception, log error, etc.
        }
        
        // Store messages in session flash data
        session()->setFlashdata('messages', $messages);

        // Redirect to the view file
        return redirect()->to('/seo');
    }

  //Method for multi url 
    public function updateMultipleUrls()
    {
    // Load the Google API Client Library
    require_once APPPATH . 'google-api-php-client/vendor/autoload.php';

    // Set up the Google API client
    $client = new Google_Client();
    $client->setApplicationName('My Indexing API');
    $client->setAuthConfig(APPPATH . '/config/futuristic-index-6241a8802f5a.json');
    $client->setScopes(['https://www.googleapis.com/auth/indexing']);

    // Create the Indexing API client   
    $indexing_service = new Google_Service_Indexing($client);

    // Get a Guzzle HTTP Client (assuming you have Guzzle installed via Composer)
    $httpClient = new GuzzleHttpClient(['base_uri' => 'https://indexing.googleapis.com']);

    // Endpoint for URL Notifications
    $endpoint = '/v3/urlNotifications:publish';

    // Handle uploaded CSV file
    $uploadedFile = $this->request->getFile('csv_file');

    // Initialize messages array
    $messages = [];

    if (!$uploadedFile->isValid() || $uploadedFile->getClientMimeType() !== 'text/csv') {
        $messages[] = 'Error: Please upload a valid CSV file.';
    } else {
        // Read CSV file contents
        $file = fopen($uploadedFile->getTempName(), 'r');

        if (!$file) {
            $messages[] = 'Error: Unable to open CSV file.';
        } else {
            try {
                // Iterate through CSV rows
                while (($row = fgetcsv($file)) !== false) {
                    // $row[0] should contain the URL
                    $url = trim($row[0]); // Trim to remove any leading/trailing whitespace

                    if (!empty($url)) {
                        // Define contents here. Ensure JSON formatting and proper escaping.
                        $content = json_encode([
                            'url' => $url,
                            'type' => 'URL_UPDATED'
                        ]);

                        // Set headers and make the request
                        $headers = [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $client->fetchAccessTokenWithAssertion()['access_token'],
                        ];

                        try {
                            // Make HTTP POST request
                            $response = $httpClient->post($endpoint, [
                                'headers' => $headers,
                                'body' => $content,
                            ]);

                            // Check response status
                            if ($response->getStatusCode() === 200) {
                                // Handle successful response
                                $messages[] = 'Successfully pinged URL: ' . $url;
                            } else {
                                // Handle unsuccessful response
                                $messages[] = 'Error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
                            }
                        } catch (\GuzzleHttp\Exception\RequestException $e) {
                            // Handle request exception
                            $messages[] = 'Request Exception: ' . $e->getMessage();
                        }
                    }
                }

                fclose($file);
            } catch (\Exception $e) {
                $messages[] = 'Exception: ' . $e->getMessage();
                // Handle exception, log error, etc.
            }
        }
    }

    // Store messages in session flash data
    session()->setFlashdata('messages', $messages);

    // Redirect to the view file
    return redirect()->to('/seo');
  }



}
