<?php

namespace WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class  AbstractWorkflow
{
    abstract public function nodeTransitions();

    abstract public function fieldNodeAttributes();
    abstract public function getWorkflowDesc();

    static public function checkWorkflowTransitionsValid(AbstractWorkflow $workflow)
    {
        //检查是否转换规则有效，比如路径走不到end， 一个节点同时是&与|逻辑等，node名中包含&|符号
        //todo
    }

    /**
     * @param AbstractWorkflow $workflow
     * @return string[]
     */
    static public function getArrayFieldList(AbstractWorkflow $workflow)
    {
        $nt = $workflow->nodeTransitions();
        $transitions = $nt["transitions"];
        $arrayFieldNodeList = [];
        foreach ($transitions as $k => $v) {
            if (is_array($v) && (count($v) == 1)) {
                if (is_string($v[0])) {
                    if (strpos($v[0],"&n") === 0) {
                        $segs = explode("&n", $v[0]);
                        if (isset($segs[1]) && $segs[1]) {
                            $arrayFieldNodeList[] = $segs[1];
                        }
                    }
                }
            }
        }
        $arrayFieldList = [];
        $fna = $workflow->fieldNodeAttributes();
        foreach ($arrayFieldNodeList as $node) {
            foreach ($fna as $filed => $filedNodeAttributes) {
                $fnaa = $filedNodeAttributes["nodeAttributes"];
                foreach ($fnaa as $nodeAttribute) {
                    if (in_array($nodeAttribute, [$node."+W", $node."+RW"])) {
                        if (!in_array($filed, $arrayFieldList)) {
                            $arrayFieldList[] = $filed;
                        }
                    }
                }
            }
        }
        return $arrayFieldList;
    }


    /**
     * @param AbstractWorkflow $workflow
     * @return string[]
     */
    static public function getForkStartNodeList(AbstractWorkflow $workflow)
    {
        $forkStartNodeList = [];
        $nt = $workflow->nodeTransitions();
        $transitions = $nt["transitions"];
        foreach ($transitions as $k => $v) {
            $forkStartNode = null;
            if (is_array($v)) {
                if (count($v) >= 2) {
                    $forkStartNode = $k;
                }
                if (count($v) == 1) {
                    if (strpos($v[0], "&n") === 0) {
                        $forkStartNode = $k;
                    }
                    if (strpos($v[0], "|n") === 0) {
                        $forkStartNode = $k;
                    }
                }
            }
            if ($forkStartNode && (!in_array($k, $forkStartNodeList))) {
                $forkStartNodeList[] = $forkStartNode;
            }
        }
        return $forkStartNodeList;
    }

    /**
     * @param AbstractWorkflow $workflow
     * @return string[]
     */
    static public function getInForkingNodeList(AbstractWorkflow $workflow)
    {
        $inForkingNodeList = [];
        $nt = $workflow->nodeTransitions();
        $transitions = $nt["transitions"];
        foreach ($transitions as $k => $v) {
            if (is_array($v)) {
                if (count($v) >= 2) {
                    foreach ($v as $vv) {
                        if ($vv && (!in_array($vv, $inForkingNodeList))) {
                            $inForkingNodeList[] = $vv;
                        }
                    }
                }
                if (count($v) == 1) {
                    if (strpos($v[0], "&n") === 0) {
                        $segs = explode("&n", $v[0]);
                        if (isset($segs[1])) {
                            if ($segs[1] && (!in_array($segs[1], $inForkingNodeList))) {
                                $inForkingNodeList[] = $segs[1];
                            }
                        }
                    }
                    if (strpos($v[0], "|n") === 0) {
                        $segs = explode("|n", $v[0]);
                        if (isset($segs[1])) {
                            if ($segs[1] && (!in_array($segs[1], $inForkingNodeList))) {
                                $inForkingNodeList[] = $segs[1];
                            }
                        }
                    }
                }
            }
        }
        return $inForkingNodeList;
    }

    /**
     * @param AbstractWorkflow $workflow
     * @return string[]
     */
    static public function getJunctionNodeList(AbstractWorkflow $workflow)
    {
        $junctionNodeList = [];
        $nt = $workflow->nodeTransitions();
        $transitions = $nt["transitions"];
        foreach ($transitions as $k => $v) {
            if (strpos($k, "&") !== false) {
                if (is_string($v)) {
                    if (!in_array($v, $junctionNodeList)) {
                        $junctionNodeList[] = $v;
                    }
                }
                continue;
            }
            if (strpos($k, "|") !== false) {
                if (is_string($v)) {
                    if (!in_array($v, $junctionNodeList)) {
                        $junctionNodeList[] = $v;
                    }
                }
            }
        }
        return $junctionNodeList;
    }

    /**
     * @param AbstractWorkflow $workflow
     * @return string[]
     */
    static public function getNormalOneNextNodeList(AbstractWorkflow $workflow)
    {
        $normalNodeList = [];
        $forkStartNodeList = self::getForkStartNodeList($workflow);
        $inForkingNodeList = self::getInForkingNodeList($workflow);
        $junctionNodeList = self::getJunctionNodeList($workflow);
        $nt = $workflow->nodeTransitions();
        $allNodeList = $nt['nodeList'];
        foreach ($allNodeList as $node) {
            if (
                (!in_array($node, $forkStartNodeList))
                && (!in_array($node, $inForkingNodeList))
            ) {
                $normalNodeList[] = $node;
            }
        }

        return $normalNodeList;
    }

    /**
     * @param AbstractWorkflow $workflow
     * @param string $forkStartNode
     * @return string
     */
    static public function getJunctionNodeAfterForkStartNodeType(AbstractWorkflow $workflow, $forkStartNode)
    {
        $nt = $workflow->nodeTransitions();
        $transitions = $nt["transitions"];

        foreach ($transitions as $k => $v) {
            if ($k === $forkStartNode) {
                if (count($v) === 1) {
                    $nextKey = null;
                    if (strpos($v[0],"&n") === 0) {
                        $nextKey = $v[0];
                    }
                    if (strpos($v[0],"|n") === 0) {
                        $nextKey = $v[0];
                    }
                    if (isset($transitions[$nextKey])&&is_string($transitions[$nextKey])) {
                        return $transitions[$nextKey];
                    }
                } else {
                    $andNextKey = implode("&", $v) ;
                    if (isset($transitions[$andNextKey])&&is_string($transitions[$andNextKey])) {
                        return $transitions[$andNextKey];
                    }
                    $orNextKey = implode("|", $v) ;
                    if (isset($transitions[$orNextKey])&&is_string($transitions[$orNextKey])) {
                        return $transitions[$orNextKey];
                    }
                }
            }
        }
        throw new \RuntimeException("ForkStartNode $forkStartNode 不存在下一个聚合节点");
    }

    /**
     * @param AbstractWorkflow $workflow
     * @param string $inForkingNode
     * @return null|string 返回 "&" "|"  "&n" "|n" 四种类型 或 null
     */
    static public function getInForkingNodeType(AbstractWorkflow $workflow, $inForkingNode)
    {
        $nt = $workflow->nodeTransitions();
        $transitions = $nt["transitions"];
        foreach ($transitions as $k => $v) {
            if (is_array($v)) {
                if (count($v) >= 2) {
                    if (in_array($inForkingNode, $v)) {
                        $andKey = implode("&", $v);
                        if (isset($transitions[$andKey])) {
                            return "&";
                        }
                        $orKey = implode("|", $v);
                        if (isset($transitions[$orKey])) {
                            return "|";
                        }
                    }
                }
                if (count($v) == 1) {
                    if ($v[0] === ("&n".$inForkingNode)) {
                        return "&n";
                    }
                    if ($v[0] === ("|n".$inForkingNode)) {
                        return "|n";
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param AbstractWorkflow $workflow
     * @return string
     */
    static public function getLauncherNode(AbstractWorkflow $workflow)
    {
        $nt = $workflow->nodeTransitions();
        return $nt['nodeList'][0];
    }


    /**
     * @var string
     *
     * @ORM\Column(name="workflow_name", type="string", length=255)
     */
    public $workflowName;

    /**
     * @var string
     *
     * @ORM\Column(name="current_finished_node", type="string", length=255)
     */
    public $currentFinishedNode;

    /**
     * @var int
     *
     * @ORM\Column(name="finished_time", type="bigint", nullable=true)
     */
    public $finishedTime;

    /**
     * @var string
     *
     * @ORM\Column(name="current_assigned_staff_id", type="string", length=255)
     */
    public $currentAssignedStaffId;

    /**
     * @var string
     *
     * @ORM\Column(name="launched_staff_id", type="string", length=255)
     */
    public $launchedStaffId;

    /**
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * @param string $workflowName
     * @return AbstractWorkflow
     */
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentFinishedNode()
    {
        return $this->currentFinishedNode;
    }

    /**
     * @param string $currentFinishedNode
     * @return AbstractWorkflow
     */
    public function setCurrentFinishedNode($currentFinishedNode)
    {
        $this->currentFinishedNode = $currentFinishedNode;
        return $this;
    }

    /**
     * @return int
     */
    public function getFinishedTime()
    {
        return $this->finishedTime;
    }

    /**
     * @param int $finishedTime
     * @return AbstractWorkflow
     */
    public function setFinishedTime($finishedTime)
    {
        $this->finishedTime = $finishedTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentAssignedStaffId()
    {
        return $this->currentAssignedStaffId;
    }

    /**
     * @param string $currentAssignedStaffId
     * @return AbstractWorkflow
     */
    public function setCurrentAssignedStaffId($currentAssignedStaffId)
    {
        $this->currentAssignedStaffId = $currentAssignedStaffId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLaunchedStaffId()
    {
        return $this->launchedStaffId;
    }

    /**
     * @param string $launchedStaffId
     * @return AbstractWorkflow
     */
    public function setLaunchedStaffId($launchedStaffId)
    {
        $this->launchedStaffId = $launchedStaffId;
        return $this;
    }


    /**
     * @ORM\Column(type="integer")
     * @ORM\Version
     */
    protected $version;

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }


    /**
     * @var int
     * 使用这个工作流的商户id
     * @ORM\Column(name="shop_id", type="integer")
     */
    public $shopId;

    /**
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param int $shopId
     * @return AbstractWorkflow
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
        return $this;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}