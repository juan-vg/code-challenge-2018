<?php

namespace AppBundle\Domain\Entity\Game;

use AppBundle\Domain\Entity\Ghost\Ghost;
use AppBundle\Domain\Entity\Maze\Maze;
use AppBundle\Domain\Entity\Player\Player;
use J20\Uuid\Uuid;

/**
 * Domain entity: Game
 *
 * @package AppBundle\Domain\Entity\Game
 */
class Game
{
    const STATUS_NOT_STARTED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_FINISHED = 9;

    /** @var Maze */
    protected $maze;

    /** @var Player[] */
    protected $players;

    /** @var Ghost[] */
    protected $ghosts;

    /** @var int */
    protected $ghostRate;

    /** @var int */
    protected $minGhosts;

    /** @var int */
    protected $status;

    /** @var int */
    protected $moves;

    /** @var string */
    protected $uuid;

    /**
     * Game constructor.
     *
     * @param Maze $maze
     * @param Player[] $players
     * @param Ghost[] $ghosts
     * @param int $ghostRate
     * @param int $minGhosts
     * @param int $status
     * @param int $moves
     * @param string $uuid
     */
    public function __construct(
        Maze $maze,
        array $players,
        array $ghosts,
        $ghostRate = 0,
        $minGhosts = 0,
        $status = self::STATUS_NOT_STARTED,
        $moves = 0,
        $uuid = null
    ) {
        $this->maze = $maze;
        $this->players = $players;
        $this->ghosts = $ghosts;
        $this->ghostRate = $ghostRate;
        $this->minGhosts = $minGhosts;
        $this->status = $status;
        $this->moves = $moves;
        $this->uuid = $uuid ?: Uuid::v4();
    }

    /**
     * Get maze
     *
     * @return Maze
     */
    public function maze()
    {
        return $this->maze;
    }

    /**
     * Get Players
     *
     * @return Player[]
     */
    public function players()
    {
        return $this->players;
    }

    /**
     * Get Ghosts
     *
     * @return Ghost[]
     */
    public function ghosts()
    {
        return $this->ghosts;
    }

    /**
     * Get ghost rate
     *
     * @return int
     */
    public function ghostRate()
    {
        return $this->ghostRate;
    }

    /**
     * Get min ghosts
     *
     * @return int
     */
    public function minGhosts()
    {
        return $this->minGhosts;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get moves
     *
     * @return int
     */
    public function moves()
    {
        return $this->moves;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * Returns if the game is playing
     *
     * @return bool
     */
    public function playing()
    {
        return static::STATUS_RUNNING == $this->status;
    }

    /**
     * Returns if the game is finished
     *
     * @return bool
     */
    public function finished()
    {
        return static::STATUS_FINISHED == $this->status;
    }

    /**
     * Starts playing the game
     *
     * @return $this
     */
    public function startPlaying()
    {
        $this->status = static::STATUS_RUNNING;
        return $this;
    }

    /**
     * Stops playing the game
     *
     * @return $this
     */
    public function stopPlaying()
    {
        if ($this->status != static::STATUS_FINISHED) {
            $this->status = static::STATUS_NOT_STARTED;
        }
        return $this;
    }

    /**
     * Ends playing the game
     *
     * @return $this
     */
    public function endGame()
    {
        $this->status = static::STATUS_FINISHED;
        return $this;
    }

    /**
     * Resets the game to its initial position
     *
     * @return $this
     */
    public function resetPlaying()
    {
        $this->moves = 0;
        $this->ghosts = array();
        $this->status = static::STATUS_NOT_STARTED;
        foreach ($this->players as $player) {
            $player->reset($this->maze()->start());
        }
        return $this;
    }

    /**
     * Get the height of the maze
     *
     * @return int
     */
    public function height()
    {
        return $this->maze()->height();
    }

    /**
     * Get the width of the maze
     *
     * @return int
     */
    public function width()
    {
        return $this->maze()->width();
    }

    /**
     * Increments the moves counter
     *
     * @return $this
     */
    public function incMoves()
    {
        $this->moves++;
        return $this;
    }

    /**
     * Adds a ghost
     *
     * @param Ghost $ghost
     * @return $this
     */
    public function addGhost(Ghost $ghost)
    {
        $this->ghosts[] = clone $ghost;
        return $this;
    }

    /**
     * Removes a ghost
     *
     * @param Ghost $ghost
     * @return $this
     */
    public function removeGhost(Ghost $ghost)
    {
        foreach ($this->ghosts as $key => $item) {
            if ($ghost == $item) {
                unset($this->ghosts[$key]);
                break;
            }
        }
        return $this;
    }

    /**
     * Checks if a player reached the goal
     *
     * @param Player $player
     * @return bool
     */
    public function isGoalReached(Player $player)
    {
        $pos = $player->position();
        $goal = $this->maze()->goal();
        if ($pos->y() == $goal->y() && $pos->x() == $goal->x()) {
            return true;
        }
        return false;
    }

    /**
     * Checks if there are at least one player alive
     *
     * @return bool
     */
    public function arePlayersAlive()
    {
        foreach ($this->players as $player) {
            if ($player->status() == Player::STATUS_PLAYING) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the current classification
     *
     * @return Player[]
     */
    public function classification()
    {
        $players = $this->players;
        usort($players, function (Player $p1, Player $p2) {
            if ($p1->status() == $p2->status()) {
                if ($p1->timestamp() < $p2->timestamp()) {
                    return -1;
                } elseif ($p1->timestamp() > $p2->timestamp()) {
                    return 1;
                } else {
                    return 0;
                }
            } elseif ($p1->winner() || $p2->dead()) {
                return -1;
            } else {
                return 1;
            }
        });
        return $players;
    }
}
