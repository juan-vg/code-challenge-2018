<?php

namespace AppBundle\Form\CreateContest;

use AppBundle\Domain\Entity\Contest\Contest;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form entity: ContestEntity
 *
 * @package AppBundle\Form\CreateContest
 */
class ContestEntity
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min=0, max=48)
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     * @Assert\Length(min=0, max=256)
     */
    private $regex;

    /**
     * @var \DateTime
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $startDate;

    /**
     * @var \DateTime
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $endDate;

    /**
     * @var \DateTime
     * @Assert\DateTime()
     */
    private $contestDate;

    /**
     * @var int
     * @Assert\Length(min=2)
     */
    private $maxCompetitors;

    /**
     * ContestEntity constructor
     *
     * @param Contest $contest
     * @throws \Exception
     */
    public function __construct(Contest $contest = null)
    {
        if (null === $contest) {
            $this->uuid = null;
            $this->name = null;
            $this->description = null;
            $this->regex = null;
            $this->startDate = new \DateTime();
            $this->startDate->setTime(0, 0, 0, 0);
            $this->endDate = clone $this->startDate;
            $this->endDate->add(new \DateInterval('P10D'));
            $this->endDate->setTime(23, 59, 59, 0);
            $this->contestDate = null;
            $this->maxCompetitors = null;
        } else {
            $this->uuid = $contest->uuid();
            $this->name = $contest->name();
            $this->description = $contest->description();
            $this->regex = $contest->emailRestrictionsRegex();
            $this->startDate = $contest->startRegistrationDate();
            $this->endDate = $contest->endRegistrationDate();
            $this->contestDate = $contest->contestDate();
            $this->maxCompetitors = $contest->maxCompetitors();
        }
    }

    /**
     * Converts the entity to a domain entity
     *
     * @return Contest
     * @throws \Exception
     */
    public function toDomainEntity(): Contest
    {
        return new Contest(
            $this->uuid,
            $this->name,
            $this->description,
            $this->regex,
            $this->startDate,
            $this->endDate,
            $this->contestDate,
            $this->maxCompetitors
        );
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string|null $uuid
     * @return ContestEntity
     */
    public function setUuid(?string $uuid): ContestEntity
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ContestEntity
     */
    public function setName(?string $name): ContestEntity
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return ContestEntity
     */
    public function setDescription(?string $description): ContestEntity
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRegex(): ?string
    {
        return $this->regex;
    }

    /**
     * @param string $regex
     * @return ContestEntity
     */
    public function setRegex(?string $regex): ContestEntity
    {
        $this->regex = $regex;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return ContestEntity
     */
    public function setStartDate(?\DateTime $startDate): ContestEntity
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     * @return ContestEntity
     */
    public function setEndDate(?\DateTime $endDate): ContestEntity
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getContestDate(): ?\DateTime
    {
        return $this->contestDate;
    }

    /**
     * @param \DateTime $contestDate
     * @return ContestEntity
     */
    public function setContestDate(?\DateTime $contestDate): ContestEntity
    {
        $this->contestDate = $contestDate;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxCompetitors(): ?int
    {
        return $this->maxCompetitors;
    }

    /**
     * @param int $maxCompetitors
     * @return ContestEntity
     */
    public function setMaxCompetitors(int $maxCompetitors): ContestEntity
    {
        $this->maxCompetitors = $maxCompetitors;
        return $this;
    }
}
