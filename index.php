<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

function pre($array, $die = true) {
	echo '<pre>';
	print_r($array);

	if ($die) {
		die();
	}
}
// load composer
$loader = require_once __DIR__ . '/vendor/autoload.php';

$app = new Application();

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'db.options' => array(
		'drive' => 'pdo_mysql',
		'host' => 'localhost',
		'dbname' => 'restbeer',
		'user' => 'root',
		'password' => 'etx4',
		'charset' => 'utf8',

	),
));

$app->get('/cervejas/{id}', function ($id) use ($app) {

	if ($id == null) {
		$sql = 'SELECT * FROM cervejas';
		try {
			$cervejas = $app['db']->fetchAll($sql);
		} catch (\Exception $e) {
			pre($e->getMessage());
		}

		return new Response(json_encode($cervejas), 200);
	}

	$sql = 'SELECT * FROM cervejas WHERE id = ?';
	$cerveja = $app['db']->fetchAssoc($sql, array($id));

	if (!$cerveja) {
		return new Response(json_encode('Não encontrada'), 404);
	}

	return new Response(json_encode($cerveja), 200);

})->value('id', null);

$app->post('/cervejas', function (Request $request) use ($app) {
	// pega os dados
	if (!$data = $request->get('cerveja')) {
		return new Response('Faltam Parâmetros', 400);
	}

	$app['db']->insert('cervejas', array('nome' => $data['nome'], 'estilo' => $data['estilo']));

	return $app->redirect('/cervejas/' . $data['id'], 201);
});

$app->put('/cervejas/{id}', function (Request $request, $id) use ($app) {

	// pega os dados
	if (!$data = $request->get('cerveja')) {
		return new Response('Faltam Parâmetros', 400);
	}

	$sql = 'SELECT * FROM cervejas WHERE id = ?';
	$cerveja = $app['db']->fetchAssoc($sql, array($id));

	if (!$cerveja = $app['db']->find($id)) {
		return new Response('Não encontrada', 404);
	}

	$app['db']->update(
		'cervejas',
		array('nome' => $data['nome'], 'estilo' => $data['estilo']),
		array('id' => $cerveja['id'])
	);

	return new Response('Cerveja atualizada', 200);

});

$app->delete('cervejas/{id}', function (Request $request, $id) use ($app) {

	$sql = 'SELECT *FROM cervejas WHERE nome = ?';
	$cerveja = $app['db']->fetchAssoc($sql, array($id));

	if (!$cerveja) {
		return new Response('Não encontrada', 404);
	}

	$app['db']->delete('cervejas', array('id' => $cerveja['id']));

	return new Response('Cerveja removida com sucesso', 200);
});

$app->error(function (\Exception $e, $code) {
	if ($code == '404') {
		return 'Página não encontrada';
	}

	return $e->getMessage();
});

// $app->before(function (Request $request) use ($app) {
//     if (!$request->headers->has('authorization')) {
//         return new Response('Unauthorized', 401);
//     }

//     require_once 'configs/clientes.php';
//     if (!in_array($request->headers->get('authorization'), array_keys($clientes))) {
//         return new Response('Unauthorized', 401);
//     }

// });

$app->after(function (Request $request, Response $response) {
	$response->headers->set('Content-Type', 'text/json');
});
$app['debug'] = true;
$app->run();
