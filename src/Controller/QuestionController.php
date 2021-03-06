<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use App\Service\MarkdownHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    private $logger;
    private $isDebug;

    public function __construct(LoggerInterface $logger, bool $isDebug)
    {
        $this->logger = $logger;
        $this->isDebug = $isDebug;
    }


    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(QuestionRepository $repository)
    {
        $questions = $repository->findAllAskedOrderedByNewest();
        return $this->render('question/homepage.html.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * @Route("/questions/new")
     */
    public function new(EntityManagerInterface $em){
        $question = new Question();
        $question->setName('Missing pants')
            ->setSlug('missing-pants-'.rand(0,1000))
            ->setQuestion(<<<EOF
                Hi! So... I'm having a *weird* day. Yesterday, I cast a spell
                to make my dishes wash themselves. But while I was casting it,
                I slipped a little and I think `I also hit my pants with the spell`.
                When I woke up this morning, I caught a quick glimpse of my pants
                opening the front door and walking out! I've been out all afternoon
                (with no pants mind you) searching for them.
                Does anyone have a spell to call your pants back?
                EOF
                );
        if(rand(1,10) > 2){
            $question->setAskedAt(new \DateTime(sprintf('-%d days', rand(1,100))));
        }

        $question->setVotes(rand(-20, 50));

        $em->persist($question);
        $em->flush();

        return new Response(sprintf('Well hallo! The new question is id #%d, slug: %s',
            $question->getId(),
            $question->getSlug()
        ));
    }

    /**
     * @Route("/questions/{slug}", name="app_question_show")
     */
    public function show(Question $question)
    {
        if ($this->isDebug) {
            $this->logger->info('We are in debug mode!');
        }

        $answers = [
            'Make sure your cat is sitting `purrrfectly` still ????',
            'Honestly, I like furry shoes better than MY cat',
            'Maybe... try saying the spell backwards?',
        ];
        /*$questionText = 'I\'ve been turned into a cat, any *thoughts* on how to turn back? While I\'m **adorable**, I don\'t really care for cat food.';

        $parsedQuestionText = $markdownHelper->parse($questionText);*/

        return $this->render('question/show.html.twig', [
            'question' => $question,//ucwords(str_replace('-', ' ', $slug)),
            //'questionText' => $parsedQuestionText,
            'answers' => $answers,
        ]);
    }


    /**
     * @Route("/questions/{slug}/votes", name="app_question_vote", methods="POST")
     */
    public function questionVote(Question $question, Request $request, EntityManagerInterface $em){
        //dd($question, $request->request->all());

        $direction = $request->request->get('direction');

        if($direction === 'up'){
            $question->upVote();
        }else{
            $question->downVote();
        }


        $em->flush();

        return $this->redirectToRoute('app_question_show', [
           'slug' => $question->getSlug(),
        ]);

    }



}
