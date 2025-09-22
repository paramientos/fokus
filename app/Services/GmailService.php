<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class GmailService
{
    private Google_Client $client;

    private ?Google_Service_Gmail $service = null;

    public function __construct()
    {
        $this->client = new Google_Client;
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->addScope(Google_Service_Gmail::GMAIL_READONLY);
        $this->client->addScope(Google_Service_Gmail::GMAIL_SEND);
        $this->client->addScope(Google_Service_Gmail::GMAIL_COMPOSE);

        $this->client->setAccessType('offline');

        $this->client->setPrompt('consent');

        $this->client->setIncludeGrantedScopes(true);
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        return $token;
    }

    public function setAccessToken(array $token): void
    {
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            }
        }
    }

    private function getService(): Google_Service_Gmail
    {
        if (!$this->service) {
            $this->service = new Google_Service_Gmail($this->client);
        }

        return $this->service;
    }

    public function getInbox(int $maxResults = 10): array
    {
        $service = $this->getService();
        $userId = 'me';

        try {
            $messages = [];
            $pageToken = null;

            do {
                $opt = ['maxResults' => $maxResults];
                if ($pageToken) {
                    $opt['pageToken'] = $pageToken;
                }

                $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt);

                if ($messagesResponse->getMessages()) {
                    foreach ($messagesResponse->getMessages() as $message) {
                        $msg = $service->users_messages->get($userId, $message->getId());
                        $messages[] = $this->formatMessage($msg);
                    }
                }

                $pageToken = $messagesResponse->getNextPageToken();
            } while ($pageToken && count($messages) < $maxResults);

            return $messages;
        } catch (\Exception $e) {
            throw new \Exception('Gmail API Error: '.$e->getMessage());
        }
    }

    public function sendEmail(string $to, string $subject, string $body, array $attachments = []): void
    {
        $service = $this->getService();
        $userId = 'me';

        try {
            $message = new Google_Service_Gmail_Message;
            $boundary = uniqid(rand(), true);

            $headers = [
                'to' => $to,
                'subject' => $subject,
                'content-type' => 'multipart/mixed; boundary='.$boundary,
            ];

            $email = '';
            foreach ($headers as $key => $value) {
                $email .= "$key: $value\r\n";
            }

            $email .= "\r\n--{$boundary}\r\n";
            $email .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $email .= base64_encode($body)."\r\n";

            foreach ($attachments as $attachment) {
                $email .= "\r\n--{$boundary}\r\n";
                $email .= 'Content-Type: '.$attachment['type'].'; name="'.$attachment['name']."\"\r\n";
                $email .= 'Content-Disposition: attachment; filename="'.$attachment['name']."\"\r\n";
                $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $email .= base64_encode(file_get_contents($attachment['path']))."\r\n";
            }

            $email .= "--{$boundary}--";

            $message->setRaw(base64_encode($email));
            $service->users_messages->send($userId, $message);
        } catch (\Exception $e) {
            throw new \Exception('Gmail API Error: '.$e->getMessage());
        }
    }

    private function formatMessage($message): array
    {
        $payload = $message->getPayload();
        $headers = $payload->getHeaders();

        $result = [
            'id' => $message->getId(),
            'threadId' => $message->getThreadId(),
            'subject' => '',
            'from' => '',
            'date' => '',
            'snippet' => $message->getSnippet(),
        ];

        foreach ($headers as $header) {
            switch ($header->getName()) {
                case 'Subject':
                    $result['subject'] = $header->getValue();
                    break;
                case 'From':
                    $result['from'] = $header->getValue();
                    break;
                case 'Date':
                    $result['date'] = $header->getValue();
                    break;
            }
        }

        return $result;
    }
}
