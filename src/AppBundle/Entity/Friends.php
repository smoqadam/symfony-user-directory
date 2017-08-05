<?php
/**
 * Created by PhpStorm.
 * User: saeed
 * Date: 8/3/17
 * Time: 10:48 AM.
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="friends", uniqueConstraints={@ORM\UniqueConstraint(name="user_friend", columns={"user_id", "friend_id"})})
 */
class Friends
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="myFriends")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="friendsWithMe")
     */
    protected $friend;

    /**
     * @ORM\Column(name="accepted", type="boolean")
     */
    protected $accepted;

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getFriend()
    {
        return $this->friend;
    }

    public function getAccepted()
    {
        return $this->accepted;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;
    }

    public function setFriend(User $friend)
    {
        $this->friend = $friend;
    }
}
