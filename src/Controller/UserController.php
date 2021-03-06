<?php

namespace App\Controller;

use App\Model\CartManager;
use App\Model\UserManager;

class UserController extends AbstractController
{
    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function index()
    {
        if ($_SESSION['role'] == 'admin') {
            $userManager = new UserManager();
            $users = $userManager->selectAll();
            return $this->twig->render('User/index.html.twig', ['users' => $users]);
        } else {
            header('location:/Front/index/');
        }
    }

    /**
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function add()
    {
        if ($_SESSION['role'] == 'disconnect') {
            $message="";
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $patternPass='/^\S*(?=\S{6,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/' ;
                if (empty($_POST['firstname']) && empty($_POST['lastname'])) {
                    $message= "Veuillez remplir les champs NOM et PRENOM s'il vous plaît";
                    return $this->twig->render('User/add.html.twig', ['message'=>$message]);
                } elseif (!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                    $message= "L'adresse mail est invalide";
                    return $this->twig->render('User/add.html.twig', ['message'=>$message]);
                } elseif (preg_match($patternPass, $_POST['password']) === 0) {
                    $message= "Votre mot de passe doit faire minimum 6 caractères 
                et contenir au moins une majuscule et un chiffre";
                    return $this->twig->render('User/add.html.twig', ['message'=>$message]);
                } else {
                    $userManager = new UserManager();
                    $user = [
                        'firstname' => ucfirst($_POST['firstname']),
                        'lastname' => strtoupper($_POST['lastname']),
                        'password' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                        'mail' => strtolower($_POST['mail']),
                        'tel' => $_POST['tel'],
                    ];
                    if ($userManager->checkEmail($user) === false) {
                        $userManager->insert($user);
                        header('Location:/Front/index/');
                    } else {
                        $message = "L'adresse mail est déjà enregistrée. 
                   Veuillez vous connecter ou tenter avec une autre adresse email.";
                        return $this->twig->render('User/add.html.twig', ['message'=>$message]);
                    }
                }
            }
            return $this->twig->render('User/add.html.twig');
        } else {
            header('location:/Front/index/');
        }
    }

    /**
     * @param int $id
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function update(int $id)
    {
        $userManager = new UserManager();
        $user = $userManager->selectOneById($id);
        $cartManager = new CartManager();

        $user['id']=$id;
        if ($_SESSION["user"] == $id) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $user['firstname'] = ucfirst($_POST['firstname']);
                $user['lastname'] = strtoupper($_POST['lastname']);
                $user['mail'] = strtolower($_POST['mail']);
                $user['num_Tel'] = $_POST['tel'];

                if (!empty($_POST['password'])) {
                    $patternPass = '/^\S*(?=\S{6,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/';
                    if (preg_match($patternPass, $_POST['password']) === 0) {
                        $message = "Votre mot de passe doit faire minimum 6 caractères 
                et contenir au moins une majuscule et un chiffre";
                        return $this->twig->render('User/update.html.twig', ['user' => $user, 'message' => $message]);
                    } else {
                        $user['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        $userManager->update($user);
                        $user = $userManager->selectOneById($id);
                        $cartid = $cartManager->historiqueID($id);
                        $cart = $cartManager->showCartContent($cartid['id']);
                        $message = "Votre compte a bien été mis à jour";
                        return $this->twig->render(
                            'User/show.html.twig',
                            ['user' => $user, 'message' => $message, "cart"=>$cart]
                        );
                    }
                } else {
                    $userManager->update($user);

                    $user = $userManager->selectOneById($id);
                    $cartid = $cartManager->historiqueID($id);
                    $cart = $cartManager->showCartContent($cartid['id']);
                    $message = "Votre compte a bien été mis à jour";
                    return $this->twig->render(
                        'User/show.html.twig',
                        ['user' => $user, 'message' => $message, "cart"=>$cart]
                    );
                }
            }
            return $this->twig->render('User/update.html.twig', ['user' => $user]);
        } elseif ($_SESSION['role'] == 'admin') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $user['firstname'] = ucfirst($_POST['firstname']);
                    $user['lastname'] = strtoupper($_POST['lastname']);
                    $user['mail'] = strtolower($_POST['mail']);
                    $user['num_Tel'] = $_POST['tel'];
                    $userManager->update($user);
                    header("Location: /User/index");
            }
            return $this->twig->render('User/update.html.twig', ['user' => $user]);
        } else {
            header('location:/Account/login/');
        }
    }

    /**
     * @param int $id
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function show(int $id)
    {
        if ($_SESSION["user"] == $id) {
            $userManager = new UserManager();
            $cartManager = new CartManager();

            $user = $userManager->selectOneById($id);
            $cartid = $cartManager->historiqueID($id);
            if ($cartid) {
                $cartid = $cartid['id'];
                $cart = $cartManager->showCartContent($cartid);
                $concepts = $cartManager->conceptInCart($cartid);
                $recap = $cartManager->showPriceCart($cartid);
                return $this->twig->render('User/show.html.twig', [
                    'user' => $user,
                    "cart" => $cart,
                    "concepts"=>$concepts,
                    "recap"=>$recap,
                ]);
            } else {
                return $this->twig->render('User/show.html.twig', [
                    'user' => $user,
                ]);
            }
        } else {
            header('location:/Account/login/');
        }
    }

    public function showAd($id)
    {
        if ($_SESSION["role"] == "admin") {
            $userManager = new UserManager();
            $cartManager = new CartManager();

            $user = $userManager->selectOneById($id);
            $cartid = $cartManager->historiqueID($id);
            if ($cartid) {
                $cartid = $cartid['id'];
                $cart = $cartManager->showCartContent($cartid);
                $concepts = $cartManager->conceptInCart($cartid);
                $recap = $cartManager->showPriceCart($cartid);
                return $this->twig->render('User/showAdmin.html.twig', [
                    'user' => $user,
                    "cart" => $cart,
                    "concepts"=>$concepts,
                    "recap"=>$recap,
                ]);
            } else {
                return $this->twig->render('User/showAdmin.html.twig', [
                    'user' => $user,
                ]);
            }
        } else {
            header('location:/Account/login/');
        }
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        if ($_SESSION["user"] == $id || $_SESSION["role"] == "admin") {
            $userManager = new UserManager();
            $userManager->delete($id);
            header('Location:/user/index');
        } else {
            header('location:/Account/login/');
        }
    }
}
