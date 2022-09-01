<?php


require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;
use Slim\Middleware\MethodOverrideMiddleware;

use function Symfony\Component\String\s;

$container = new Container();

$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$repo = new App\UserRepository();
$router = $app->getRouteCollector()->getRouteParser();

//////////////////////////////////////////////////////////////////////
///                 ПОЛУЧИТЬ СПИСОК ПОЛЬЗОВАТЕЛЕЙ                   //
//////////////////////////////////////////////////////////////////////
$app->get('/users', function ($request, $response) use ($repo) {

    $flash = $this->get('flash')->getMessages();
    $users = $repo->all();
    $term = $request->getQueryParam('term');
    
    $result = collect($users)->filter(
        fn($user) => empty($term) ? true : s($user['name'])->ignoreCase()->startsWith($term)
    );
    $params = [
        'users' => $result, 
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');
//////////////////////////////////////////////////////////////////////
///              ФОРМА СОЗДАНИЯ НОВОГО ПОЛЬЗОВАТЕЛЯ                 //
//////////////////////////////////////////////////////////////////////
$app->get('/users/create', function ($request, $response) {
    $params = [
        'users' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/create.phtml', $params);
})->setName('users.create');
//////////////////////////////////////////////////////////////////////
///                 СОЗДАНИЕ НОВОГО ПОЛЬЗОВАТЕЛЯ                    //
//////////////////////////////////////////////////////////////////////
$app->post('/users', function ($request, $response) use ($repo, $router) {
    
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    
    if (count($errors) === 0) {
        $repo->save($user);
        $this->get('flash')->addMessage('success', 'Пользователь был добавлен');
        return $response->withRedirect($router->urlFor('users.index'));
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/create.phtml', $params);
})->setName('users.store');
//////////////////////////////////////////////////////////////////////
///                 ПОЛУЧИТЬ ОДНОГО ПОЛЬЗОВАТЕЛЯ                    //
//////////////////////////////////////////////////////////////////////
$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});
$app->run();
///////////////////////////////////////////////////////////////////////////////////
///                   ФОРМА ОБНОВЛЕНИЯ ДАННЫХ ПОЛЬЗОВАТЕЛЯ                      ///
///////////////////////////////////////////////////////////////////////////////////

$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($repo) {
    dd('test');
    $user = $repo->find($args['id']); // id пользователя
    $params = [
        'user' => $user,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

///////////////////////////////////////////////////////////////////////////////////
///                       ОБНОВЛЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ                        ///
///////////////////////////////////////////////////////////////////////////////////
$app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $updateUser = $request->getParsedBodyParam('user'); //новые данные 
    $validator = new Validator();
    $errors = $validator->validate($updateUser);

    if (count($errors) === 0) {
        $userUp = collect($users)->map(function ($item, $key) use ($id, $updateUser) {
            if ($id == $item['id']) {
                $updateUser['id'] = $id;
                return $updateUser;
            }
            return $item;
        }); //меняем старые данные на новые

        $this->get('flash')->addMessage('success', 'User has been updated');

        $url = $router->urlFor('get-users');
        $userUncode = json_encode($userUp);
        return $response->withHeader('Set-Cookie', "users={$userUncode}")
            ->withRedirect($url);
    }

    $params = [
        'errors' => $errors,
        'data' => $updateUser,
        'userID' => $id
    ];

    return $this->get('renderer')
        ->render($response->withStatus(422), 'users/edit.phtml', $params);
});
