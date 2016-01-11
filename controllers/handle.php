<?php

use \Fuel\Core\Input,
    \Fuel\Core\Format,
    \Fuel\Core\Response;

/**
 * The Handle Controller.
 *
 * This class consists of ajax method which is used for ajax processing
 *
 * TODO: revisions for each type of actions INSERT, UPDATE, DELETE, GET
 * TODO: add error code for thowns
 *
 * @package  ajax_handle
 * @extends  Controller
 */
class Controller_Handle extends \CMF\Controller\Base
{
    /**
     * @param $module string
     * @return mixed
     * @throws Exception
     */
    public function action_ajax($module)
    {
        $result = array(
            'error' => false,
            'message' => ''
        );

        $params = Input::all();

        try {
            if (array_intersect_key(Input::get(), Input::post()))
                throw new Exception('Get and post mustn\'t have the same keys');

            $class_name = 'Model_' . $module;

            if (!class_exists($class_name) OR $class_name::ajax() !== true)
                return Response::forge(\View::forge('errors/404.twig', array('msg' => "That page couldn't be found!")), 404);

            //TODO: check for crsf token

            $class = new $class_name();

            // NOTE: it is possible to process each kind of request by using
            // GET, POST, DELETE, PUT
            // But not all of the browsers supports this methods for using in forms
            $method = Input::get('method', 'GET');

            switch ($method) {
                case 'INSERT' :
                    if (!($message = $class::validation($params)))
                    {
                        $result['error'] = true;
                        $result['message'] = $class::get_message('insert_failed');
                    }
                    else
                    {
                        $class::insert($params);
                        $result['message'] = $class::get_message('insert_success');
                    }
                    break;
                case 'UPDATE':
                    $action = Input::get('action', 'update');
                    if (!method_exists($class, $action))
                    {
                        throw new Exception("Method {$action} doesn't exists");
                    }
                    $message = $class::$action($params);

                    $result['message'] = $class::get_message($message);


                    break;
                case 'DELETE':
                    $id = Input::get('id', false);
                    if (!$id)
                        throw new Exception('On delete method \'id\' is required');

                    if (method_exists($class, 'delete'))
                    {
                        $class::delete($id);

                        $result['message'] = $class::get_message('delete_message');
                    }

                    break;
                case 'GET':
                    $action = Input::get('action', false);
                    if (!$action)
                        throw new Exception('For get method parameter \'action\' is required');

                    if (method_exists($class, $action))
                    {
                        $result['data'] = $class::$action($params);
                        $result['message'] = $class::get_message($action);
                    }

                    break;
            }
        } catch (Exception $e)
        {
            $result = array(
                'error' => true,
                'message' => $e->getMessage()
            );
        }

        if (Input::is_ajax())
        {
            $result = Format::forge()->to_json($result);
            return Response::forge($result);
        }
        else
        {
            if (isset($params['redirect_url']))
            {
                Response::forge()->redirect($params['redirect_url']);
            }
            else
            {
                Response::forge()->redirect_back('/');
            }
        }
    }

}
