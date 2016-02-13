<?php
/**
 * @file
 * Contains \Drupal\jsonapi\Controller\NodeController.
 */

namespace Drupal\jsonapi\Controller;

use Drupal\jsonapi\Response;
use Drupal\jsonapi\DocumentContext;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


/**
 * DemoController.
 */
class NodeController implements ContainerAwareInterface {

    use ContainerAwareTrait;

    public function handle(RouteMatchInterface $route_match, Request $request) {

        $response = $this->makeResponse($route_match, $request);

        if ($response instanceof Response && $data = $response->getResponseData()) {
            $serializer = $this->container->get('serializer');
            $output = $serializer->serialize($data, "jsonapi");
            $response->setContent($output);
            $response->headers->set('Content-Type', 'application/vnd.api+json');
        }
        return $response;
    }

    protected function makeResponse($route_match, $request) {
        $nid = $route_match->getRawParameters()->get('nid');
        $node = Node::load($nid);

        if (!$node) {
            # http://jsonapi.org/format/#error-objects
            return new Response(["errors" => [["title" => "Node not found", "detail" => "A node with nid=" . $nid . " was not found."]]], 404);
        }


        if (!$node->access('view')) {
            # http://jsonapi.org/format/#error-objects
            return new Response(["errors" => [["title" => "Access denied to node", "detail" => "Access denied to node with nid=" . $nid . "."]]], 403);
        }
        return new Response(new DocumentContext($node, $this->optionsFor($request)), 200);
    }

    protected function optionsFor($request) {
        $output = [];
        foreach($request->query->all() as $key => $value) {
            if ($key == 'debug') {
                $output['debug'] = true;
            }
            if ($key == 'include') {
                if ($value == "") {
                    $output['include'] = [];
                } else {
                    $output['include'] = array_map(function($path) {
                        return explode('.', $path);
                    }, explode(',', $value));
                }
            }
            if ($key == 'fields') {
                $output['fields'] = array_map(function($fieldList){ return explode(',', $fieldList); }, $value);
            }
        }
        return $output;
    }

}