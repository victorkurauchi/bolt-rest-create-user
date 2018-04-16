<?php

namespace Bolt\Extension\Ornito\RestCreateUser\Controller;

use Bolt\Controller\Base;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Storage\Entity;
use Bolt\Storage\Entity\Users;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Controller class.
 *
 * @author Victor Kurauchi <victorkurauchi@gmail.com>
 */
class UserController extends Base
{
    /**
     * Specify which method handles which route.
     *
     * Base route/path is '/example/url'
     *
     * {@inheritdoc}
     */
    public function addRoutes(ControllerCollection $ctr)
    {
        // /example/url/in/controller
        $ctr->post('/', [$this, 'createUser'])
            ->bind('ornito.create')
            ->before(array($this, 'before'));

        return $ctr;
    }

    /**
     * Before functions in the Rest API controller
     *
     * @param Request $request The Symfony Request
     *
     * @return abort|null
     */

    public function before(Request $request)
    {
        return null;
    }

    /**
     * Handles POST requests on /rest/user/ and return status created or not.
     *
     * @param Request $request
     *
     * @return string
     */
    public function createUser(Request $request)
    {
        $user = $this->app['users']->getEmptyUser();
        $user['email'] = $request->get('email');
        $user['displayname'] = $request->get('displayname');
        $user['username'] = $request->get('username');
        $user['password'] = $request->get('password');
        $user['roles'] = ['guest'];
        $user['enabled'] = true;

        try {
            $result = $this->app['users']->saveUser($user);
    
            // user created succesfully
            // create new founder
            if ($result == 1) {
                $repo = $this->app['storage']->getRepository('fundadores');
                $repoUsers = $this->app['storage']->getRepository(Entity\Users::class);

                $founder = $this->app['storage']->getContentObject('fundadores');
                $newestUser = $repoUsers->findOneBy([], ['id', 'DESC']);

                //set defaults
                $founder = $repo->create(
                    array(
                        'contenttype' => 'fundadores',
                        'datepublish' => date('Y-m-d'),
                        'datecreated' => date('Y-m-d'),
                        'status' => 'published'
                    )
                );

                $founder['user_id'] = $newestUser['id'];
                $founder['ownerid'] = $newestUser['id'];
                $founder['email'] = $request->get('email');
                $founder['nome'] = $request->get('nome');
                $founder['slug'] = $request->get('nome');
                $founder['senha'] = $request->get('password');
                $founder['foto'] = $request->get('foto');
                $founder['cpf'] = $request->get('cpf');
                $founder['curso'] = $request->get('curso');
                $founder['departamento'] = $request->get('departamento');
                $founder['empreendedor'] = $request->get('empreendedor');
                $founder['instituto'] = $request->get('instituto');
                $founder['ocupacao'] = $request->get('ocupacao');
                $founder['registro_academico'] = $request->get('registro_academico');
                $founder['sobrenome'] = $request->get('sobrenome');
                $founder['telefone'] = $request->get('telefone');
    
                $resultFounder = $repo->save($founder);
            }
            $response = new JsonResponse($resultFounder, Response::HTTP_OK);
        } catch (HttpException $e) {
            $response = new JsonResponse($e->getMessage(), Response::HTTP_OK);
        }

        return $response;
    }

    protected function isAllowed($what, $user = null, $contenttype = null, $contentid = null)
    {
        /** @var Token $sessionAuth */
        if ($user === null && $sessionAuth = $this->session()->get('authentication')) {
            $user = $sessionAuth->getUser()->toArray();
        }

        return $this->app['permissions']->isAllowed($what, $user, $contenttype, $contentid);
    }
}
