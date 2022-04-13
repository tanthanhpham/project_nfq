<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\User;
use App\Form\CartItemType;
use App\Repository\CartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CartService extends AbstractController
{
    /** @var User|null */
    private $userLoginInfo;

    private $cartRepository;

    public function __construct(GetUserInfo $userLogin, CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
    }

    public function addCart(array $payload)
    {
        $cartItem = $this->cartRepository->findOneBy([
            'productItem' => $payload['productItem'],
            'user' => $this->userLoginInfo->getId()
        ]);

        if (!$cartItem) {
            $cartItem = new Cart();
            $cartItem->setUser($this->userLoginInfo);
        } else {
            $amount = $cartItem->getAmount() + $payload['amount'];
            $total = $cartItem->getTotal() + $payload['total'];
            if ($amount > $cartItem->getProductItem()->getAmount()) {
                return false;
            }
            $payload['amount'] = $amount;
            $payload['total'] = $total;
        }

        $form = $this->createForm(CartItemType::class, $cartItem);
        $form->submit($payload);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->cartRepository->add($cartItem);

            return true;
        }
    }
}