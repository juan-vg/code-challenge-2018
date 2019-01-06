<?php

namespace AppBundle\Controller;

use AppBundle\Domain\Entity\Contest\Competitor;
use AppBundle\Domain\Entity\Contest\Contest;
use AppBundle\Entity\Competitor as CompetitorEntity;
use AppBundle\Entity\Contest as ContestEntity;
use AppBundle\Form\CreateContest\ContestEntity as ContestFormEntity;
use AppBundle\Form\CreateContest\ContestForm;
use AppBundle\Form\RegisterCompetitor\CompetitorEntity as CompetitorFormEntity;
use AppBundle\Form\RegisterCompetitor\CompetitorForm;
use AppBundle\Repository\CompetitorRepository;
use AppBundle\Repository\ContestRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Contest admin controller
 *
 * @package AppBundle\Controller
 * @Route("/admin/contest")
 */
class AdminContestController extends Controller
{
    /**
     * Create new contest
     *
     * @Route("/", name="admin_contest_index")
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function indexAction(Request $request) : Response
    {
        $limit = $request->query->get('limit', 200);
        $start = $request->query->get('start', 0);

        /** @var ContestRepository $repo */
        $repo = $this->getContestDoctrineRepository();

        /** @var ContestEntity[] $contestEntities */
        $contestEntities = $repo->findBy([], [
            'id' => 'desc'
        ], $limit, $start);

        $total = $repo->count([]);

        // Get array [ 'contestUuid' => string, 'competitorCount' => int]
        $competitorCounts = $this
            ->getCompetitorDoctrineRepository()
            ->countPerContest($contestEntities);

        /** @var Contest[] $contests */
        $contests = [];

        // Build contest domain entities adding the competitors count
        foreach ($contestEntities as $contestEntity) {
            /** @var Contest $contest */
            $contest = $contestEntity->toDomainEntity();
            $contest->setCountCompetitors(0);
            foreach ($competitorCounts as $competitorCount) {
                if ($competitorCount['contestUuid'] == $contest->uuid()) {
                    $contest->setCountCompetitors($competitorCount['competitorCount']);
                }
            }
            $contests[] = $contest;
        }

        return $this->render('admin/contest/index.html.twig', array(
            'contests'  => $contests,
            'start'     => $start,
            'limit'     => $limit,
            'count'     => count($contests),
            'total'     => $total
        ));
    }

    /**
     * Create new contest
     *
     * @Route("/create", name="admin_contest_create")
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function createAction(Request $request) : Response
    {
        // Create contest data entity
        $contestFormEntity = new ContestFormEntity();

        // Create the contest data form
        $form = $this->createForm(ContestForm::class, $contestFormEntity, [
            'action' => $this->generateUrl('admin_contest_create'),
            'mode'   => ContestForm::MODE_CREATE
        ]);

        // Handle the request & if the data is valid...
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contestEntity = new ContestEntity($contestFormEntity->toDomainEntity());

            $em = $this->getDoctrine()->getManager();
            $em->persist($contestEntity);
            $em->flush();

            return $this->redirectToRoute('admin_contest_view', ['uuid' => $contestEntity->getUuid()]);
        }

        return $this->render('admin/contest/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * View contest
     *
     * @Route("/{uuid}", name="admin_contest_view",
     *     requirements={"uuid": "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}"})
     *
     * @param string $uuid
     * @return Response
     * @throws \Exception
     */
    public function viewAction(string $uuid) : Response
    {
        /** @var ContestEntity $contestEntity */
        $contestEntity = $this->getContestDoctrineRepository()->findOneBy(array(
            'uuid' => $uuid
        ));

        if (!$contestEntity) {
            throw new NotFoundHttpException();
        }

        /** @var CompetitorEntity[] $competitorEntities */
        $competitorEntities = $this->getCompetitorDoctrineRepository()->findBy([
            'contestUuid' => $uuid
        ]);

        /** @var Contest $contest */
        $contest = $contestEntity->toDomainEntity();

        /** @var Competitor[] $competitors */
        $competitors = [];
        foreach ($competitorEntities as $competitorEntity) {
            $competitors[] = $competitorEntity->toDomainEntity();
        }

        return $this->render('admin/contest/view.html.twig', array(
            'contest'     => $contest,
            'competitors' => $competitors
        ));
    }

    /**
     * Edit contest
     *
     * @Route("/{uuid}/edit", name="admin_contest_edit",
     *     requirements={"uuid": "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}"})
     *
     * @param Request $request
     * @param string $uuid
     * @return Response
     * @throws \Exception
     */
    public function editAction(Request $request, string $uuid) : Response
    {
        /** @var ContestEntity $contestEntity */
        $contestEntity = $this->getContestDoctrineRepository()->findOneBy(array(
            'uuid' => $uuid
        ));

        if (!$contestEntity) {
            throw new NotFoundHttpException();
        }

        // Create contest data entity
        $contestFormEntity = new ContestFormEntity($contestEntity->toDomainEntity());

        // Create the contest data form
        $form = $this->createForm(ContestForm::class, $contestFormEntity, [
            'action' => $this->generateUrl('admin_contest_edit', [ 'uuid' => $uuid ]),
            'mode'   => ContestForm::MODE_EDIT
        ]);

        // Handle the request & if the data is valid...
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contestEntity->fromDomainEntity($contestFormEntity->toDomainEntity());

            $em = $this->getDoctrine()->getManager();
            $em->persist($contestEntity);
            $em->flush();

            return $this->redirectToRoute('admin_contest_view', ['uuid' => $contestEntity->getUuid()]);
        }

        return $this->render('admin/contest/edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Remove contest and all its competitors
     *
     * @Route("/{uuid}/remove", name="admin_contest_remove",
     *     requirements={"uuid": "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}"})
     *
     * @param string $uuid
     * @return Response
     * @throws \Exception
     */
    public function removeAction(string $uuid) : Response
    {
        /** @var ContestEntity $contestEntity */
        $contestEntity = $this->getContestDoctrineRepository()->findOneBy(array(
            'uuid' => $uuid
        ));

        if (!$contestEntity) {
            throw new NotFoundHttpException();
        }

        /** @var CompetitorEntity $competitorEntities */
        $competitorEntities = $this->getCompetitorDoctrineRepository()->findBy([
            'contestUuid' => $uuid
        ]);

        $em = $this->getDoctrine()->getManager();
        foreach ($competitorEntities as $competitorEntity) {
            $em->remove($competitorEntity);
        }
        $em->remove($contestEntity);
        $em->flush();

        return new Response('', 204);
    }

    /**
     * Create competitor for a contest without validations
     *
     * @Route("/{uuid}/competitor/register", name="admin_competitor_register",
     *     requirements={"uuid": "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}"})
     *
     * @param Request $request
     * @param string $uuid
     * @return Response
     * @throws \Exception
     */
    public function competitorRegister(Request $request, string $uuid)
    {
        /** @var ContestEntity $contestEntity */
        $contestEntity = $this->getContestDoctrineRepository()->findOneBy(array(
            'uuid' => $uuid
        ));

        if (!$contestEntity) {
            throw new NotFoundHttpException();
        }

        // Create competitor data entity
        $formEntity = new CompetitorFormEntity();
        $formEntity->setContest($contestEntity);

        // Create the competitor data form
        $form = $this->createForm(CompetitorForm::class, $formEntity, [
            'action' => $this->generateUrl('admin_competitor_register', [ 'uuid' => $uuid ]),
            'admin'  => true
        ]);

        // Handle the request & if the data is valid...
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Competitor $competitor */
            $competitor = $formEntity->toDomainEntity();
            $competitor->setValidated();

            $em = $this->getDoctrine()->getManager();
            $entity = new CompetitorEntity($competitor);
            $em->persist($entity);
            $em->flush();

            return $this->redirectToRoute('admin_contest_view', [
                'uuid' => $uuid
            ]);
        }


        return $this->render('admin/contest/register.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Remove a single competitor from a contest
     *
     * @Route("/competitor/{uuid}/remove", name="admin_competitor_remove",
     *     requirements={"uuid": "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}"})
     *
     * @param string $uuid
     * @return Response
     * @throws \Exception
     */
    public function competitorRemove(string $uuid) : Response
    {
        /** @var CompetitorEntity $competitorEntity */
        $competitorEntity = $this->getCompetitorDoctrineRepository()->findOneBy(array(
            'uuid' => $uuid
        ));

        if (!$competitorEntity) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($competitorEntity);
        $em->flush();

        return new Response('', 204);
    }

    /**
     * Validate a competitor to participate to a contest
     *
     * @Route("/competitor/{uuid}/validate", name="admin_competitor_validate",
     *     requirements={"uuid": "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}"})
     *
     * @param string $uuid
     * @return Response
     * @throws \Exception
     */
    public function competitorValidate(string $uuid) : Response
    {
        /** @var CompetitorEntity $competitorEntity */
        $competitorEntity = $this->getCompetitorDoctrineRepository()->findOneBy(array(
            'uuid' => $uuid
        ));

        if (!$competitorEntity) {
            throw new NotFoundHttpException();
        }

        /** @var Competitor $competitor */
        $competitor = $competitorEntity->toDomainEntity();
        $competitor->setValidated();
        $competitorEntity->fromDomainEntity($competitor);

        $em = $this->getDoctrine()->getManager();
        $em->persist($competitorEntity);
        $em->flush();

        return new Response('', 204);
    }

    /**
     * Return the repository object to Contest entity
     *
     * @return ContestRepository
     */
    private function getContestDoctrineRepository() : ContestRepository
    {
        return $this->getDoctrine()->getRepository('AppBundle:Contest');
    }

    /**
     * Return the repository object to Competitor entity
     *
     * @return CompetitorRepository
     */
    private function getCompetitorDoctrineRepository() : CompetitorRepository
    {
        return $this->getDoctrine()->getRepository('AppBundle:Competitor');
    }
}
