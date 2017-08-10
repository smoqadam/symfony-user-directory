<?php
/**
 * Created by PhpStorm.
 * User: saeed
 * Date: 8/3/17
 * Time: 1:51 AM.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Friends;
use AppBundle\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Elastica\Query;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class UsersController extends Controller
{
    /**
     * @Route("/", name="index")
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('users/index.html.twig');
    }

    /**
     * @Route("/user/{id}", name="user_profile")
     *
     * @param $id int
     *
     * @return Response
     */
    public function userProfile($id)
    {
        $id = $id ?: $this->getUser()->getId();
        $user = $this
            ->getDoctrine()
            ->getRepository(User::class)
            ->findOneById($id);

        return $this->render('users/profile.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Search users in Elastic.
     *
     * @Route("/search", name="search")
     *
     * @param $request Request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $search_query = $request->get('query');

        $cache = $this->get('cache.app');

        if ($search_query) {
            $user_search_cache = $cache->getItem('users_search'.$search_query);
            $user_search_cache->expiresAfter(300);
            if ($user_search_cache->isHit()) {
                $result = $user_search_cache->get();
            } else {
                $finder = $this->container->get('fos_elastica.finder.users.user');
                $boolQuery = new Query\BoolQuery();
                $innerQuery = new Query\Match();
                $innerQuery->setField('_all', array('query' => $search_query));
                $innerQuery->setFieldFuzziness('_all', 'AUTO');
                $boolQuery->addShould($innerQuery);
                $result = $finder->find($boolQuery);
                $user_search_cache->set($result);
                $cache->save($user_search_cache);
            }
        } else {
            $all_users_cache = $cache->getItem('all_users');
            $all_users_cache->expiresAfter(300);
            if ($all_users_cache->isHit()) {
                $result = $all_users_cache->get();
            } else {
                $result = $this
                ->getDoctrine()
                ->getRepository(User::class)
                ->findAll();
                $all_users_cache->set($result);
                $cache->save($all_users_cache);
            }
        }

        $myFriendIds = [];
        $myFriends = $this->getUser()->getFriends();
        if ($myFriends) {
            foreach ($myFriends as $user) {
                $myFriendIds[$user->getFriend()->getId()] = $user->getAccepted();
            }
        }

        return $this->render('users/search.html.twig', [
            'users' => $result,
            'myFriends' => $myFriendIds,
        ]);
    }

    /**
     * send friend request.
     *
     * @Route("/friends/add/{id}", name="add_as_friend")
     *
     * @param $request Request
     * @param $id int
     *
     * @return RedirectResponse
     */
    public function addAsFriendAction(Request $request, $id)
    {
        try {
            $friend = $this->getDoctrine()
                ->getRepository(User::class)
                ->findOneBy(['id' => $id]);
            $em = $this->getDoctrine()->getManager();
            $friends = new Friends();
            $friends->setFriend($friend);
            $friends->setUser($this->getUser());
            $friends->setAccepted(false);
            $em->persist($friends);
            $em->flush();

            $request->getSession()->getFlashBag()->add('success', 'User added as friend');
        } catch (UniqueConstraintViolationException $e) {
            $request->getSession()->getFlashBag()->add('error', 'User added before');
        }

        return $this->redirect('/friends');
    }

    /**
     * Friends List.
     *
     * @Route("/friends", name="friends")
     *
     * @return Response
     */
    public function friendsList()
    {
        $repository = $this->getDoctrine()->getRepository(Friends::class);
        $friends = $repository->findBy(
            [
                'user' => $this->getUser()->getId(),
                'accepted' => true,
            ]
        );

        return $this->render('users/friends.list.html.twig', [
            'friends' => $friends,
        ]);
    }

    /**
     * Received friend request from other users.
     *
     * @Route("/friends/requests", name="friend_requests")
     *
     * @return Response
     */
    public function friendRequests()
    {
        $requests = $this->getDoctrine()
            ->getRepository(Friends::class)
            ->findBy([
                'friend' => $this->getUser()->getId(),
                'accepted' => false,
            ]);

        return $this->render('users/requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    /**
     * Accept a friend request.
     *
     * @Route("/friends/accept/{friend_id}", name="accept_friend")
     *
     * @param $request Request
     * @param $friend_id int
     *
     * @return RedirectResponse
     */
    public function acceptFriend(Request $request, $friend_id)
    {
        $user_repository = $this->getDoctrine()->getRepository(User::class);
        $user = $user_repository->findOneById($friend_id);
        if (!$user) {
            throw $this->createNotFoundException("User id {$friend_id} not found");
        }
        $em = $this->getDoctrine()->getManager();

        // update accepted in friends table
        $friend_repository = $this->getDoctrine()->getRepository(Friends::class);
        $friend = $friend_repository->findOneBy([
                        'friend' => $this->getUser()->getId(),
                        'user' => $friend_id,
                        'accepted' => false,
                    ]);
        $friend->setAccepted(true);

        // add new record for current user
        $myFriend = new Friends();
        $myFriend->setFriend($user);
        $myFriend->setUser($this->getUser());
        $myFriend->setAccepted(true);
        $em->persist($myFriend);
        $em->flush();

        $request
            ->getSession()
            ->getFlashBag()
            ->add('success', "You and {$friend->getUser()->getName()} are now friends");

        return $this->redirect('/friends/requests');
    }
}
