<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\EdgesNoded;
use geoPHP;

class APIRouteController extends Controller
{
    public function route(Request $req)
    {
        try {
            $edgesNoded = new EdgesNoded();
            $startVertex = $edgesNoded->getVertex($req->start['lat'], $req->start['lon']);
            $endVertex = $edgesNoded->getVertex($req->end['lat'], $req->end['lon']);
            if (count($startVertex) < 1 || count($endVertex) < 1) {
                $apiResp = [
                    'meta' => [
                        'status' => false,
                        'message' => 'location is far away from route line transjakarta',
                    ],
                ];
                return (new Response($apiResp, 400));
            }
            $result = $edgesNoded->routeTransJakartaLine($startVertex[0]->id, $endVertex[0]->id);
            $features = [
                'type' => 'FeatureCollection',
                'name' => 'route_transjakarta_line',
                "crs" => ["type" => "name", "properties" => ["name" => "urn:ogc:def:crs:OGC:1.3:CRS84"]],
                'features' => [],
            ];
            for ($i = 0; $i < count($result); $i++) {
                $geom = geoPHP::load($result[$i]->geom, 'wkt');
                // https://stackoverflow.com/questions/34911046/getting-geojson-linestring-from-mysql-geometry-wkt-data
                // https://github.com/phayes/geoPHP
                array_push($features['features'], [
                    "type" => "Feature",
                    "properties" => [
                        'seq' => $result[$i]->seq,
                        'edge' => $result[$i]->edge,
                        'name' => $result[$i]->name,
                    ],
                    "geometry" => json_decode($geom->out('json')),
                ]);
            }
            $apiResp = [
                'meta' => [
                    'status' => true,
                    'message' => 'success get route',
                ],
                'data' => $features
            ];
            return (new Response($apiResp, 200));
        } catch (\Exception $e) {
            $apiResp = [
                'meta' => [
                    'status' => false,
                    'message' => $e->getMessage(),
                ],
            ];
            return (new Response($apiResp, 500));
        }
    }
}
