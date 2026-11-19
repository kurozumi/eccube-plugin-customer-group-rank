<?php

/*
 * This file is part of CustomerGroupRank
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\CustomerGroupRank42\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\CustomerGroupRank42\Form\Type\Admin\ConfigType;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * 会員ランクの設定（プラグイン一覧の「設定」から開く）。
 *
 * @IsGranted("ROLE_ADMIN")
 *
 * @Route("/%eccube_admin_route%/customer_group_rank")
 */
class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    private ConfigRepository $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/config", name="customer_group_rank42_admin_config", methods={"GET", "POST"})
     */
    public function index(Request $request): Response
    {
        // テーブルがまだ無い（ファイルだけ更新して schema-update を打っていない）
        // ときは、保存できないことを画面で知らせる。判定は OR のまま動いている。
        if (!$this->configRepository->hasTable()) {
            return $this->render('@CustomerGroupRank42/admin/config.twig', [
                'form' => null,
            ]);
        }

        $config = $this->configRepository->getOrCreate();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('customer_group_rank42_admin_config');
        }

        return $this->render('@CustomerGroupRank42/admin/config.twig', [
            'form' => $form->createView(),
        ]);
    }
}
