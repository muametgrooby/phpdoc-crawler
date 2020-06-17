<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MainController extends AbstractController
{
    /**
     * @Route("/function/{pattern}")
     */
    public function function($pattern, Request $request, UrlGeneratorInterface $urlgenerator): Response
    {
        $client = HttpClient::create();

        $functionRequest = $client->request('GET', 'https://www.php.net/manual/en/function.' . $pattern);

        if ($functionRequest->getStatusCode() != 404) {
            $dom = new Crawler($functionRequest->getContent());
            $parameters = $dom->filter('dt > code.parameter');

            $params = [];

            for ($i = 0; $i < $parameters->count(); $i++) {
                $params[$i] = [
                    $parameters->getNode($i)->textContent,
                    trim($dom->filter('dd .para')->getNode($i)->textContent)
                ];
            }

            $json = array(
                "name" => $dom->filter(".refname")->text(),
                "version_info" => $dom->filter('.verinfo')->text(),
                "description" => array(
                    "code" => $dom->filter('.dc-description')->text(),
                    "text" => $dom->filter('.rdfs-comment')->text(),
                ),
                "parameters" => $params
            );
            return $this->json($json);
        }

        $response = $client->request('GET', 'https://www.php.net/manual-lookup.php?pattern=' . $pattern);

        $site = new Crawler($response->getContent());

        $li = $site->filter('#quickref_functions')->children();

        $data = [];

        for ($i = 0; $i < $li->count(); $i++) {
            $data[$i] = $li->getNode($i)->firstChild;
            $data[$i] = array(
                'name' => $li->getNode($i)->firstChild->textContent,
                'href' => $request->getSchemeAndHttpHost() . $urlgenerator->generate(
                    "app_main_function",
                    ['pattern' => \str_replace('_', '-', $li->getNode($i)->firstChild->textContent)]
                ),
                'source' => 'https://www.php.net/' . $li->getNode($i)->firstChild->getAttribute("href")
            );
        }

        return $this->json($data);
    }
}
