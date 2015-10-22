<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// load composer 
$loader = require_once __DIR__.'/vendor/autoload.php';

$app = new Application();

$cervejas = array(
    'marcas' => array('Heineken','Stela','Brahma','Skol'),
    'estilos' => array('Pilsen','Stout'),
);

$app->get('/cervejas/{id}', function ($id) use ($cervejas) {

    if ($id == null) {
        $result = implode(',', $cervejas['marcas']);

        return new Response(json_encode($result), 200);
    }

    $key = array_key_exists($id, $cervejas['marcas']);

    if ($key === false) {
        return new Response(json_encode('Ceveja cabo'), 404);
    }

    return new Response(json_encode($cervejas['marcas'][$id]), 200);

})->value('id', null);

$app->post('/cervejas', function (Request $request) use ($app) {
    // pega os dados 
    if (!$data = $request->get('cerveja')) {
        return new Response('Faltam Parâmetros', 400);
    }

    // Persiste na base de dados (considerando uma entidade do Doctrine nesse exemplo)
    $cerveja = new Cerveja();
    $cerveja->nome = $data['nome'];
    $cerveja->estilo = $data['estilo'];

    $cerveja->save();

    // redireciona para a nova cerveja
    return $app->redirect('/cervejas/'.$data['id'], 201);
});

$app->get('/estilos', function () use ($cervejas) {
    return implode(',', $cervejas['estilos']);
});

$app->error(function (\Exception $e, $code) {
     if ($code == '404') {
         return 'Página não encontrada';
     }

     return $e->getMessage();
});

$app->before(function (Request $request) use ($app) {
    if (!$request->headers->has('authorization')) {
        return new Response('Unauthorized', 401);
    }

    require_once 'configs/clientes.php';
    if (!in_array($request->headers->get('authorization'), array_keys($clientes))) {
        return new Response('Unauthorized', 401);
    }

});

$app->after(function (Request $request, Response $response) {
    $response->headers->set('Content-Type', 'text/json');
});

$app->run();
