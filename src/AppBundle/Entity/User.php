<?php
/**
 * Created by PhpStorm.
 * User: saeed
 * Date: 8/2/17
 * Time: 10:53 PM.
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     * @Assert\Length(
     *     max=255,
     *     maxMessage="The name is too long.",
     *     groups={"Registration", "Profile"}
     * )
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Length(
     *      max=255,
     *     maxMessage="the Last Name is too long.",
     *     groups={"registration", "Profile"}
     * )
     */
    protected $last_name;

    /**
     * @ORM\Column(type="integer")
     *
     * @Assert\Type(
     *     type="integer",
     *     message="The age must be number"
     * )
     */
    protected $age;

    /**
     * @ORM\OneToMany(targetEntity="Friends", mappedBy="user")
     */
    protected $myFriends;

    /**
     * @ORM\OneToMany(targetEntity="Friends", mappedBy="friend")
     */
    protected $friendsWithMe;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $register_date;

    public function __construct()
    {
        parent::__construct();
        $this->myFriends = new ArrayCollection();
        $this->friendsWithMe = new ArrayCollection();
        if (empty($this->register_date)) {
            $this->register_date = new \DateTime();
        }
    }

    /**
     * add name, last name, age to serialized data.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical,
            $this->name,
            $this->age,
            $this->last_name,
        ));
    }

    /**
     * override unserialize for adding extra field.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical,
            $this->name,
            $this->age,
            $this->last_name) = $data;
    }

    public function addAsFriend(User $myFriend)
    {
        $friend = new Friends();
        $friend->setUser($this);
        $friend->setFriend($myFriend);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function getRegisterDate()
    {
        return $this->register_date;
    }

    public function getFriends()
    {
        return $this->myFriends;
    }

    public function getFriendsWithMe()
    {
        return $this->friendsWithMe;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function setRegisterDate($date)
    {
        $this->register_date = $date;
    }
}
