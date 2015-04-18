<?php

namespace Eccube\Controller;

use Eccube\Application;
use Eccube\Framework\Util\Utils;
use Symfony\Component\Security\Core\Util\SecureRandom;


class EntryController extends AbstractController
{
    private $title;

    public $form;

    public function __construct()
    {
        $this->title = '会員登録';

    }

    public function kiyaku(Application $app)
    {
        $app['session']->remove('entry');
        $kiyaku = '規約内容を取得して表示';
        // TODO: 規約内容を取得
        // $kiyaku = $app['orm.em']
        //     ->getRepository('Eccube\Entity\Kiyaku')
        //     ->findAll();

        $form = $app['form.factory']->createBuilder()->getForm();

        return $app['twig']->render('Entry/kiyaku.twig', array(
            'title' => $this->title,
            'kiyaku' => $kiyaku,
            'form' => $form->createView(),
        ));
    }

    public function index(Application $app)
    {
        $customer = $app['eccube.repository.customer']->newCustomer();

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $app['form.factory']->createBuilder('customer', $customer);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        if ($app['request']->getMethod() === 'POST') {
            $form->handleRequest($app['request']);
            if ($form->isValid()) {
                switch ($app['request']->get('mode')) {
                    case 'confirm' :
                        $builder->setAttribute('freeze', true);
                        $form = $builder->getForm();
                        $form->handleRequest($app['request']);

                        return $app['twig']->render('Entry/confirm.twig', array(
                            'title' => $this->title,
                            'form' => $form->createView(),
                        ));
                        break;
                    case 'complete':

                        $customer->setSecretKey($this->getUniqueSecretKey($app));

                        // password
                        $generator = new SecureRandom();
                        $salt = bin2hex($generator->nextBytes(10));
                        $customer->setSalt($salt);
                        $encoder = $app['security.encoder_factory']->getEncoder($customer);
                        $encoded_password = $encoder->encodePassword($customer->getPassword(), $customer->getSalt());
                        $customer->setPassword($encoded_password);

                        $app['orm.em']->persist($customer);
                        $app['orm.em']->flush();

                        $data = $form->getData();

                        // TODO: 後でEventとして実装する
                        // $app['eccube.event.dispatcher']->dispatch('customer.regist::after');
                        $message = $app['mail.message']
                            ->setSubject('[EC-CUBE3] 会員登録が完了しました。')
                            ->setFrom(array('sample@example.com'))
                            ->setCc($app['config']['mail_cc'])
                            ->setTo(array($customer->getEmail()))
                            ->setBody('会員登録が完了しました。');
                        $app['mailer']->send($message);

                        return $app->redirect($app['url_generator']->generate('entry_complete'));
                        break;
                }
            }
        }

        return $app['view']->render('Entry/index.twig', array(
            'title' => $this->title,
            'form' => $form->createView(),
        ));
    }


    public function complete(Application $app)
    {

        return $app['view']->render('Entry/complete.twig', array(
            'title' => $this->title,
        ));
    }


    // ユニークなキーを取得する
    private function getUniqueSecretKey($app)
    {
        $unique = md5(uniqid(rand(), 1));
        $customer = $app['eccube.repository.customer']->findBy(array(
            'secret_key' => $unique,
        ));
        if (count($customer) == 0) {
            return $unique;
        } else {
            return $this->getUniqueSecretKey($app);
        }
    }

}