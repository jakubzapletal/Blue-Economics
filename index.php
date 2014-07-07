<?php

require 'vendor/autoload.php';

$env = 'dev';
//$env = 'prod';

// app instance
$app = new Silex\Application([
	'debug' => $env == 'dev' ? true : false,
	'config.path' => 'config/' . $env . '/',
]);

// add mysql service
$app['mysql'] =  $app->share(function ($app) {
	$config = parse_ini_file($app['config.path'] . 'mysql.ini');
	$pdo = new PDO("mysql:host=" . $config['db.hostname'] . ";dbname=" . $config['db.schema'], $config['db.user'], $config['db.password']);
	// set the character set to utf8 to ensure proper json encoding
	$pdo->exec("SET NAMES 'utf8'");

	return $pdo;
});

// add log service
$app['log'] = $app->share(function($app) {
    Logger::configure($app['config.path'] . 'log4php-config.xml');
    return Logger::getLogger('default');
});

class Db
{
	/**
	 * @var \Silex\Application
	 */
	public $app;

	public function __construct(Silex\Application $app) {
		$this->app = $app;
	}

	public function execute($query, array $params = []) {
		$this->app['log']->debug(sprintf("Executing query: %s with params: %s", $query, json_encode($params)));
		$mysql = $this->app['mysql'];
		$handler = $mysql->prepare($query);
		$handler->execute($params);
		return $handler->fetchAll(PDO::FETCH_OBJ);
	}
}

// add db service
$app['db'] = $app->share(function($app) {
	return new Db($app);
});

// FIXME: Implement separation of view and data

// TODO: move index.html into the /views directory and
// point the templates to /views

// main page
$app->get('/', function () use ($app) {
    return require './index.html';
});

// api example
$app->get('/api', function () use ($app) {
    $res = $app['db']->execute("SELECT * FROM filters LIMIT 10");
    foreach($res as $row) {
        echo $row->Name;
        echo "<br>";
    };
	return '';
});

// industry example
$app->get('/industries', function () use ($app) {
    $res = $app['db']->execute("SELECT DISTINCT Id, Name FROM industries ORDER BY Name");
    foreach($res as $row){
        echo "<a href=\"#\" onclick=\"return loadJob(";
        echo $row->Id;
        echo ")\" class=\"selectable_result\">" . $row->Name . "</a>";
        echo "<br />";
    };
	return '';
});

// jobs example
$app->get('/jobs', function () use ($app) {
    $res = empty($_GET) ? $app['db']->execute("SELECT DISTINCT Name FROM occupations ORDER BY Name") : $app['db']->execute("SELECT DISTINCT Name FROM occupations WHERE IndustryId = :industry ORDER BY Name", array(':industry' => intval($_GET['industry'])));
    foreach($res as $entry) {        
        $row = $entry->Name;
        echo "<a href=\"#\" onclick=\"return loadJobDetails('$row')\" class=\"selectable_result\">$row</a>";
        echo "<br>";    
    };
	return '';
});

// jobs example
$app->get('/job_description', function () use ($app) {
    $job = rawurldecode($_SERVER["QUERY_STRING"]);
    $res = $app['db']->execute('SELECT DISTINCT Name, Description, MedianPayAnnual, MedianPayHourly, NumberOfJobs, EmploymentOpenings FROM occupations WHERE Name = :jobName', array(':jobName' => $job));
	return $app->json($res);
});

$app->get('/workexperience/:id', function($id) use($app) {
    $res = $app['db']->execute('SELECT DISTINCT Id, Name FROM workexperiences WHERE id = :id', array(':id' => $id));
    $result = [];
    foreach ($res as $entry) {
        array_push($result, array( 'id' => $id, 'name' => $entry->Name));
    }
    return $app->json($result);
});

$app->get('/workexperience', function() use($app) {
    $res = $app['db']->execute('SELECT DISTINCT Id, Name FROM workexperiences');
    $result = [];
    foreach($res as $entry) {
        array_push($result, array('id' => $entry->Id, 'name' => $entry->Name));        
    }
	return $app->json($result);
});

$app->get('/search/:searchQuery', function($searchQuery) use($app) {
    $result = array('industries' => [], 'jobs' => []);

    // find matching industries
    $query = "SELECT Id, Name FROM industries WHERE MATCH(Name) AGAINST ( '$searchQuery' )";
    $res = $app['db']->execute($query);
    $industries = [];
    foreach($res as $industry) {
        $industries[$industry->Id] = array( 'id' => $industry->Id, 'name' => $industry->Name);
    }

    // find matching jobs
    $jobs = [];
    $query = "SELECT DISTINCT i.Id as IndustryId, i.Name as IndustryName, o.Name as JobName FROM occupations o, industries i WHERE o.IndustryId = i.Id AND MATCH(o.Description, o.Name) AGAINST ( '$searchQuery' )";
    $res = $app['db']->execute($query);
    foreach($res as $job) {
        array_push($jobs, array( 'name' => $job->JobName));
        // add job industry to industries list
        $industries[$job->IndustryId] = array('id' => $job->IndustryId, 'name' => $job->IndustryName);
    }

    $result = array('industries' => array_values($industries), 'jobs' => $jobs);

	return $app->json($result);
});

$app->post('/occupations', function() use($app) {
    $result = [];
    if (isset($_POST['education'])) {
        $optionArray = $_POST['education'];
        array_walk($optionArray, function($value, $index) {
            $value = explode(",", $value);
        });
        $edLevels = implode(",", $optionArray);
        //$app->log->info(sprintf("Education levels %s", $edLevels));
        $res = $app['db']->execute("SELECT DISTINCT Name FROM occupations WHERE  EducationLevelId in ( $edLevels ) ORDER BY Name");
    } else {
        $res  = $app['db']->execute('SELECT DISTINCT Name FROM occupations ORDER BY Name');
    } 
    foreach($res as $occupation) {
        array_push($result, (array) $occupation);
    }
	return $app->json($result);
});

$app->run();
?>