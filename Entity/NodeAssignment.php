<?php

namespace WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NodeAssignment
 *
 * @ORM\Table(name="node_assignment", indexes={
 *     @ORM\Index(name="shopId_idx", columns={"shop_id"}),
 *     @ORM\Index(name="workflowClassName_idx", columns={"workflow_class_name"}),
 *     @ORM\Index(name="prevNodeAssignmentId_idx", columns={"prev_node_assignment_id"}),
 * })
 * @ORM\Entity(repositoryClass="WorkflowBundle\Repository\NodeAssignmentRepository")
 */
class NodeAssignment
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="prev_node_assignment_id", type="integer", nullable=true)
     */
    public $prevNodeAssignmentId;

    /**
     * @var int
     *
     * @ORM\Column(name="shop_id", type="integer")
     */
    public $shopId;

    /**
     * @var string
     *
     * @ORM\Column(name="assigned_staff_id", type="string", length=255)
     */
    public $assignedStaffId;

    /**
     * @var int
     *
     * @ORM\Column(name="workflow_id", type="integer")
     */
    public $workflowId;


    /**
     * @var string
     *
     * @ORM\Column(name="workflow_class_name", type="string", length=255)
     */
    public $workflowClassName;

    /**
     * @var string
     *
     * @ORM\Column(name="assigned_node", type="string", length=255)
     */
    public $assignedNode;

    /**
     * @var int
     *
     * @ORM\Column(name="node_status", type="integer")
     */
    public $nodeStatus;

    /**
     * @var int
     *
     * @ORM\Column(name="assigned_time", type="bigint")
     */
    public $assignedTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_workflow_launcher", type="boolean")
     */
    public $isWorkflowLauncher;

    /**
     * @var int
     *
     * @ORM\Column(name="after_forked_nodes_finished_next_junction_node_assigned_staff_id", type="integer", nullable=true)
     */
    public $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId;

    /**
     * @var int
     *
     * @ORM\Column(name="finished_time", type="bigint", nullable=true)
     */
    public $finishedTime;

    /**
     * @var string
     *
     * @ORM\Column(name="submitted_data", type="text", nullable=true)
     */
    public $submittedData;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set prevNodeAssignmentId
     *
     * @param integer $prevNodeAssignmentId
     *
     * @return NodeAssignment
     */
    public function setPrevNodeAssignmentId($prevNodeAssignmentId)
    {
        $this->prevNodeAssignmentId = $prevNodeAssignmentId;

        return $this;
    }

    /**
     * Get prevNodeAssignmentId
     *
     * @return int
     */
    public function getPrevNodeAssignmentId()
    {
        return $this->prevNodeAssignmentId;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return NodeAssignment
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return int
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Set assignedStaffId
     *
     * @param string $assignedStaffId
     *
     * @return NodeAssignment
     */
    public function setAssignedStaffId($assignedStaffId)
    {
        $this->assignedStaffId = $assignedStaffId;

        return $this;
    }

    /**
     * Get assignedStaffId
     *
     * @return string
     */
    public function getAssignedStaffId()
    {
        return $this->assignedStaffId;
    }

    /**
     * Set workflowId
     *
     * @param integer $workflowId
     *
     * @return NodeAssignment
     */
    public function setWorkflowId($workflowId)
    {
        $this->workflowId = $workflowId;

        return $this;
    }

    /**
     * Get workflowId
     *
     * @return int
     */
    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    /**
     * Set workflowClassName
     *
     * @param string $workflowClassName
     *
     * @return NodeAssignment
     */
    public function setWorkflowClassName($workflowClassName)
    {
        $this->workflowClassName = $workflowClassName;

        return $this;
    }

    /**
     * Get workflowClassName
     *
     * @return string
     */
    public function getWorkflowClassName()
    {
        return $this->workflowClassName;
    }

    /**
     * Set assignedNode
     *
     * @param string $assignedNode
     *
     * @return NodeAssignment
     */
    public function setAssignedNode($assignedNode)
    {
        $this->assignedNode = $assignedNode;

        return $this;
    }

    /**
     * Get assignedNode
     *
     * @return string
     */
    public function getAssignedNode()
    {
        return $this->assignedNode;
    }

    /**
     * Set nodeStatus
     *
     * @param integer $nodeStatus
     *
     * @return NodeAssignment
     */
    public function setNodeStatus($nodeStatus)
    {
        $this->nodeStatus = $nodeStatus;

        return $this;
    }

    /**
     * Get nodeStatus
     *
     * @return int
     */
    public function getNodeStatus()
    {
        return $this->nodeStatus;
    }

    /**
     * Set assignedTime
     *
     * @param integer $assignedTime
     *
     * @return NodeAssignment
     */
    public function setAssignedTime($assignedTime)
    {
        $this->assignedTime = $assignedTime;

        return $this;
    }

    /**
     * Get assignedTime
     *
     * @return int
     */
    public function getAssignedTime()
    {
        return $this->assignedTime;
    }

    /**
     * Set isWorkflowLauncher
     *
     * @param boolean $isWorkflowLauncher
     *
     * @return NodeAssignment
     */
    public function setIsWorkflowLauncher($isWorkflowLauncher)
    {
        $this->isWorkflowLauncher = $isWorkflowLauncher;

        return $this;
    }

    /**
     * Get isWorkflowLauncher
     *
     * @return bool
     */
    public function getIsWorkflowLauncher()
    {
        return $this->isWorkflowLauncher;
    }

    /**
     * Set afterForkedNodesFinishedNextJunctionNodeAssignedStaffId
     *
     * @param integer $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId
     *
     * @return NodeAssignment
     */
    public function setAfterForkedNodesFinishedNextJunctionNodeAssignedStaffId($afterForkedNodesFinishedNextJunctionNodeAssignedStaffId)
    {
        $this->afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId;

        return $this;
    }

    /**
     * Get afterForkedNodesFinishedNextJunctionNodeAssignedStaffId
     *
     * @return int
     */
    public function getAfterForkedNodesFinishedNextJunctionNodeAssignedStaffId()
    {
        return $this->afterForkedNodesFinishedNextJunctionNodeAssignedStaffId;
    }

    /**
     * Set finishedTime
     *
     * @param integer $finishedTime
     *
     * @return NodeAssignment
     */
    public function setFinishedTime($finishedTime)
    {
        $this->finishedTime = $finishedTime;

        return $this;
    }

    /**
     * Get finishedTime
     *
     * @return int
     */
    public function getFinishedTime()
    {
        return $this->finishedTime;
    }

    /**
     * @return array
     */
    public function getSubmittedData()
    {
        return json_decode($this->submittedData, true);
    }

    /**
     * @param array $submittedData
     * @return NodeAssignment
     */
    public function setSubmittedData($submittedData)
    {
        $this->submittedData = json_encode($submittedData);
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


    const STATUS_NEW = 0;
    const STATUS_FINISHED = 1;
    const STATUS_OBSOLETE_FOR_ROLLBACK = 2;
    const STATUS_OBSOLETE_FOR_WORKFLOW_STOPPED = 3;
    const STATUS_OBSOLETE_FOR_SIBLING_IN_FORKING_NODE_FINISHED = 4;
}

