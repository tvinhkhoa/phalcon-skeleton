<?php
error_reporting(1);
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
if (!extension_loaded('phalcon')) {
	$prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
	dl($prefix . 'phalcon.' . PHP_SHLIB_SUFFIX);
//    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//        dl('php_phalcon.dll');
//    } else {
//        dl('phalcon.so');
//    }
}
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://');
$baseUrl .= getenv('HTTP_HOST');

define('BASE_URL', $baseUrl);

// Define ROOT path off application
define('ROOT_PATH', realpath(dirname(__FILE__) .'/../'));

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', ROOT_PATH . '/Application');

// Define
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Session\Bag as SessionBag;

include_once ROOT_PATH . '/Initialize/php-initialize.php';

try {
	// Initiation DI
    $di = new \Phalcon\DI\FactoryDefault();
	
	// load Config
	// check config save cache
	$config = new \Phalcon\Config\Adapter\Ini(APPLICATION_PATH . '/Configs/config.ini');
	$registry = new \Phalcon\Registry();
	//$registry->config = $config;

    // Đăng ký Loader
    $config_loader = array(
        ROOT_PATH . $config->application->controllersDir, // Khai báo Folder Controller
        ROOT_PATH . $config->application->pluginsDir,
        ROOT_PATH . $config->application->libraryDir,
        ROOT_PATH . $config->application->modelsDir,
        ROOT_PATH . $config->application->formsDir,
	);
    $loader = new \Phalcon\Loader();
    $loader->registerDirs($config_loader)->registerNamespaces( array(
		'Application'	=> APPLICATION_PATH,
    ))->register();
	
    // Set registry for project
	$di->setShared('registry', $registry);
	
	// Registering config shared
	$di->setShared('config', $config);
	
    // Câu hình url tới project, vì mình nằm trong project_name nên phải khai báo
    $di->set('url', function(){
        $url = new \Phalcon\Mvc\Url();
		$url->setBaseUri(BASE_URL);
        return $url;
    });
	
	// Set route
	$di->set('router', function (){
		$router = new Phalcon\Mvc\Router();
//		$router->setDefaultModule('frontend');
		$router->notFound(array(
			'module'     => 'Frontend',
			'controller' => 'Error',
			'action' => 'show404'
		));
		
		// Module Frontend
		$router->add('/', 
					 array('module' => 'Frontend','controller'=>'Index','action' => 'index'))->setName('default');
		$router->add('/:controller/:action/:params\.html', 
					 array('module'=>'Frontend','controller'=> 1,'action'=> 2,'params'=> 3));
		$router->add('/:action\.html',
					 array('module'=> 'Frontend','controller' => 'Index','action'=> 1));
		
		// Module Backend
		$router->add('/admin',
					 array('module'=> 'Backend','controller'=>'Index','action'=> 'index'));
		$router->add('/admin/',
					 array('module' => 'Backend','controller' => 'Index','action' => 'index'));
		$router->add('/admin/:controller',
					 array('module'=> 'Backend','controller' => 1,'action'=> 'index'));
		$router->add('/admin/:controller/:action/:params',
					 array('module'=> 'Backend','controller' => 1,'action'=> 2,'params'=> 3));
		
		return $router;
	});
	
	// Set storage
//	$di->set('storage', function () {
//		return new Storage('/some/directory');
//	}, true);
	
	// ----------Single Module --------------
    // Registering view
//    $di->set('view', function(){
//        $view = new \Phalcon\Mvc\View();
//        $view->setViewsDir(APPLICATION_PATH . '/views/');
//        return $view;
//    });
	
	// Set and start Session the first time a component requests the session service	
//	$di->setShared('session', function() {
//		$session = new \Phalcon\Session\Adapter\Files();
//		$session->setoptions([
//			'uniqueId' => 'phalcon2_',
//		]);
//		$session->start();
//		return $session;
//	});
	//---------- End Single Module -------------

	// Multi modules
	$modules = array();
	foreach($config->modules->module as $item) {
		$modules[$item] = array(
			'className' => $config->modules->{$item}->namespace,
			'path' => APPLICATION_PATH . ($config->modules->{$item}->path) . 'Module.php'
		);
	}
	//print_r($modules);exit;
    // Khởi tạo ứng dụng
    $application = new \Phalcon\Mvc\Application();
	$application->setDI($di);
	$application->registerModules($modules);
	
	// Register the installed modules
//	$application->registerModules(
//        array(
//            'frontend' => array(
//                'className' => 'Modules\Frontend\Module',
//                'path'      => APPLICATION_PATH. '/frontend/Module.php',
//            ),
//            'backend'  => array(
//                'className' => 'Modules\Backend\Module',
//                'path'      => APPLICATION_PATH .'/backend/Module.php',
//            )
//        )
//    );

    // Xử lý và hiển thị kết quả
	echo $application->handle()->getContent();
	
} catch(\Phalcon\Exception $e) {
     echo 'PhalconException: ', $e->getMessage();
}