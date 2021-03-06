<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../models/articles.class.php';

$app = new Silex\Application();
$app['debug'] = true;

// Services
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'hetic-p2018-blog-G1',
        'user'      => 'root',
        'password'  => 'root',
        'charset'   => 'utf8'
    ),
));
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => array(
        'host'       => 'smtp.gmail.com',
        'port'       => 465,
        'username'   => 'hetic.p2018.smtp@gmail.com',
        'password'   => 'heticp2018smtp',
        'encryption' => 'ssl',
        'auth_mode'  => 'login'
    )
));

$app['db']->setFetchMode(PDO::FETCH_OBJ);

// Models
$articles_model = new Articles_Model($app['db']);


$app->get('/', function() use ($app,$articles_model) {

	$data = array(
	    'title' => 'Home',
	    'articles' => $articles_model->get_all()
	);

	return $app['twig']->render('page.twig',$data);
})
->bind('home');


$app->get('/page/{number}', function($number) use ($app) {

	$data = array(
	    'title' => 'Page'
	);

	return $app['twig']->render('page.twig',$data);
})
->assert('number','[0-9]+')
->bind('page');


$app->get('/article/{article}', function($article) use ($app) {

	$data = array(
	    'title' => 'Article'
	);

	return $app['twig']->render('article.twig',$data);
})
->assert('article','[a-z0-9_-]+')
->bind('article');


$app->get('/category/{category}', function($category) use ($app) {

	$data = array(
	    'title' => 'Category'
	);

	return $app['twig']->render('category.twig',$data);
})
->assert('category','[a-z0-9_-]+')
->bind('category');


$app->get('/about', function() use ($app) {

	$data = array(
	    'title' => 'About'
	);

	return $app['twig']->render('about.twig',$data);
})
->bind('about');


$app->match('/contact', function(Request $request) use ($app) {

	$data = array(
	    'title' => 'Contact'
	);

	// Create form builder
	$form_builder = $app['form.factory']->createBuilder('form');

	// Set form builder params
	$form_builder->setAction($app['url_generator']->generate('contact'));
	$form_builder->setMethod('post');

	// Add inputs
	$form_builder->add(
		'name',
		'text',
		array(
			'label'       => 'Your name',
			'trim'        => true,
			'max_length'  => 20,
			'constraints' => array(
				new Constraints\NotEqualTo(
					array(
						'value'   => 'fuck',
						'message' => 'Be polite you shithead'
					)
				)
			)
		)
	);
	$form_builder->add(
		'email',
		'email',
		array(
			'label' => 'Your email',
			'trim'  => true
		)
	);
	$form_builder->add(
		'subject',
		'choice',
		array(
			'expanded' => true,
			'choices'  => array(
				'sujet 1' => 'sujet 1',
				'sujet 2' => 'sujet 2',
				'sujet 3' => 'sujet 3'
			)
		)
	);
	$form_builder->add(
		'message',
		'textarea',
		array(
			'label' => 'Message',
			'trim'  => true
		)
	);
	$form_builder->add(
		'submit',
		'submit'
	);

	// Create form
	$form = $form_builder->getForm();

	// Handle request
	$form->handleRequest($request);

	// Test if form submitted
	if($form->isSubmitted())
	{
		$form_data = $form->getData();

		// Test if form is valid
		if($form->isValid())
		{
			// Prepare message
			$message = \Swift_Message::newInstance();
			$message->setSubject($form_data['subject']);
			$message->setFrom($form_data['email']);
			$message->setTo('bruno.simon@hetic.net');
			$message->setBody($form_data['message']);

			// Send email
			$app['mailer']->send($message);

			// Redirect (to reset form)
			return $app->redirect($app['url_generator']->generate('contact'));
		}
	}

	// Create view to be use in twig
	$data['form'] = $form->createView();

	return $app['twig']->render('contact.twig',$data);
})
->bind('contact');



$app->run();
