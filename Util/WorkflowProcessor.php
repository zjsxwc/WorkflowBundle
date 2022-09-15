<?php


namespace WorkflowBundle\Util;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use WorkflowBundle\Entity\AbstractWorkflow;
use WorkflowBundle\Entity\NodeAssignment;
use WorkflowBundle\Repository\NodeAssignmentRepository;

class WorkflowProcessor
{
    /**
     * WorkflowProcessor constructor.
     * @param EntityManager|null $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->nodeAssignmentRepository = $entityManager->getRepository("WorkflowBundle:NodeAssignment");
    }

    /**
     * 返回接下来节点名 与 这些节点的类型， 分支节点的下一个汇合节点是由 ForkStartNode分支发起节点确定了的所以不需要返回
     * @param AbstractWorkflow $workflow
     * @param $node
     * @return array
     */
    public function getNextNodeAndType(AbstractWorkflow $workflow, $node)
    {
        //todo
    }

    public function getCurrentNodeMap(AbstractWorkflow $workflow, $node)
    {
        //todo
    }


    /**
     * @param AbstractWorkflow $workflow
     * @param string $node
     */
    public function getNodeFieldDataAttributesList(AbstractWorkflow $workflow, $node)
    {
        $nodeFieldDataAttributesList = [];
        $fna = $workflow->fieldNodeAttributes();
        foreach ($fna as $field => $fieldNodeAttributes) {
            $fieldDisplayName = $fieldNodeAttributes["displayName"];
            $fnaa = $fieldNodeAttributes["nodeAttributes"];
            foreach ($fnaa as $nodeAttribute) {
                if (in_array($nodeAttribute, [$node."+R", $node."+W", $node."+RW"])) {
                    $getFieldMethodName = "get".ucfirst($field);
                    $value = $workflow->{$getFieldMethodName}();
                    if ($nodeAttribute === $node."+W") {
                        $value = null;
                    }

                    $nodeFieldDataAttributes = [
                        "field" => $field,
                        "fieldDisplayName" => $fieldDisplayName,
                        "value" => $value,
                        "rw" => explode("+", $nodeAttribute)[1]
                    ];
                    $nodeFieldDataAttributesList[] = $nodeFieldDataAttributes;
                }
            }
        }
        return $nodeFieldDataAttributesList;
    }

    /** @var NodeAssignmentRepository */
    protected $nodeAssignmentRepository = null;
    /** @var EntityManager  */
    protected $entityManager = null;

    /**
     * @param AbstractWorkflow $workflow
     * @param string $node
     * @param array $data
     * @param string $currentStaffId
     * @param NodeAssignment $currentNodeAssignment
     */
    public function processNodeSubmittedData(AbstractWorkflow $workflow, $node, $data, $currentStaffId, $currentNodeAssignment)
    {
        $conn = $this->entityManager->getConnection();
        $retryTimes = 0;
        START_PROCESS:
        $retryTimes += 1;
        $conn->beginTransaction();
        try {
            $this->doProcessNodeSubmittedData($workflow,$node,$data,$currentStaffId,$currentNodeAssignment);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            if ($retryTimes > 5) {
                throw $e;
            }
            $randSecs = rand(1, 20);
            if ($e->getCode() === 10001) {
                sleep($randSecs);
                $this->entityManager->refresh($workflow);
                goto START_PROCESS;
            }
            if (in_array($e->getCode(),[10002,10003])) {
                sleep($randSecs);
                $this->entityManager->refresh($currentNodeAssignment);
                goto START_PROCESS;
            }

            throw $e;
        }
        return $currentNodeAssignment;
    }


    /**
     * @param AbstractWorkflow $workflow
     * @param string $node
     * @param array $data
     * @param string $currentStaffId
     * @param NodeAssignment $currentNodeAssignment
     */
    protected function doProcessNodeSubmittedData(AbstractWorkflow $workflow, $node, $data, $currentStaffId, &$currentNodeAssignment)
    {
        if (isset($data["nextNodeDesc"])&&is_string($data["nextNodeDesc"])) {
            $data["nextNodeDesc"] = json_decode($data["nextNodeDesc"],true);
        }
        if ($currentStaffId === null) {
            if (!$currentNodeAssignment) {
                throw new \RuntimeException("不能同时没有 currentStaffId, currentNodeAssignment");
            }
            $currentStaffId = $currentNodeAssignment->getAssignedStaffId();
        }
        if (!$currentNodeAssignment) {
            if ($node === AbstractWorkflow::getLauncherNode($workflow)) {
                $currentNodeAssignment = new NodeAssignment();
                $currentNodeAssignment
                    ->setAssignedNode($node)
                    ->setAssignedStaffId($currentStaffId)
                    ->setAssignedTime(time())

                    ->setIsWorkflowLauncher(true)
                    ->setNodeStatus(NodeAssignment::STATUS_NEW)
                    ->setShopId($workflow->getShopId())
                    ->setSubmittedData($data)
                    ->setWorkflowClassName(get_class($workflow))
                    ->setWorkflowId($workflow->getId())
                ;
                $this->nodeAssignmentRepository->storeNodeAssignment($currentNodeAssignment);
            } else {
                throw new \RuntimeException("currentNodeAssignment不提供时只有 node是workflow的发起节点才允许");
            }
        } else {
            $this->entityManager->refresh($currentNodeAssignment);
            if ($currentNodeAssignment->getNodeStatus() !== NodeAssignment::STATUS_NEW) {
                throw new \RuntimeException("当前节点已经被处理，不能再次处理");
            }
        }


        //保存提交的data数据到 $workflow， 这个$workflow获取后必须加事务，必须对这个workflow加乐观锁
        //https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/transactions-and-concurrency.html#optimistic-locking
        // $em->lock($entity, LockMode::OPTIMISTIC, $expectedVersion);
        //$post = $em->find('BlogPost', $postId, \Doctrine\DBAL\LockMode::OPTIMISTIC, $versionFromSessionWhichIsStoredByFirstRead);
        //$versionFromSessionWhichIsStoredByFirstRead 值是第一次get请求读取时把version存到session中的，这里第二次post请求 尝试读取或者lock数据时用到这个version
        //flush执行真正保存（被persist缓存的）数据到数据库时给这个version加1
        //https://www.doctrine-project.org/projects/doctrine-orm/en/2.13/reference/annotations-reference.html#version
        //如果flush失败 就clear 后重新find  或者 直接refresh获取数据库最新数据

        /** @var string[] $shouldSubmittedField */
        $shouldSubmittedField = [];
        $nodeFieldDataAttributesList = $this->getNodeFieldDataAttributesList($workflow, $node);
        foreach ($nodeFieldDataAttributesList as $fieldDataAttributes) {
            if (in_array($fieldDataAttributes['rw'],["W","RW"])){
                $shouldSubmittedField[] = $fieldDataAttributes['field'];
            }
        }

        /** @var string[] $arrayFieldList */
        $arrayFieldList = AbstractWorkflow::getArrayFieldList($workflow);

        //给 $workflow 对应的字段赋值，注意对于数组字段赋值操作
        foreach ($shouldSubmittedField as $filed) {
            $value = null;
            if (isset($data[$filed])) {
                $value = $data[$filed];
            }
            $setFieldMethod = "set".ucfirst($filed);
            $getFieldMethod = "get".ucfirst($filed);
            if (!in_array($filed, $arrayFieldList)) {
                $workflow->{$setFieldMethod}($value);
            } else {
                //处理数组字段
                $arrayData = $workflow->{$getFieldMethod}();
                if (is_string($arrayData)) {
                    $arrayData = json_decode($arrayData, true);
                }
                if (!$arrayData) {
                    $arrayData = [];
                }
                $arrayData[] = $value;
                $workflow->{$setFieldMethod}(json_encode($arrayData));
            }

        }

        $this->entityManager->persist($workflow);
        try {
            $this->entityManager->flush($workflow);
        } catch (OptimisticLockException $e) {
            //todo 外面如果catch到error code为10001就重新rollback ， 再refresh **workflow**后重试
            throw new \RuntimeException("workflow outdated", 10001, $e);
        }



        /*
         * 上面data除了包含要提交的workflow 字段外，
         * 也包含一个字段nextNodeDesc 当前表单代表的节点node要 转换 到的下一个节点以及节点执行人ID,结构如下：
        //如果是处于分支节点就不需要指派nextNodeDesc，因为上一个分配分支的节点用afterForkedNodesFinishedNextJunctionNodeAssignedStaffId指派了
nextNodeDesc=[
{nextNode: "C", assignedStaffId: "123"},//表示对员工id为123的执行人，分配填写本次工作流程下节点C的表单
{nextNode: "D", assignedStaffId: "456"},
]
//如果是分配分支的节点就还需要指派 分支的聚合节点人
afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = 123
         *
         */

        $nextNodeDesc = null;
        if (isset($data["nextNodeDesc"])) {
            $nextNodeDesc = $data["nextNodeDesc"];
        }
        $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = null;
        if (isset($data["afterForkedNodesFinishedNextJunctionNodeAssignedStaffId"]) && is_numeric($data["afterForkedNodesFinishedNextJunctionNodeAssignedStaffId"])) {
            $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = intval($data["afterForkedNodesFinishedNextJunctionNodeAssignedStaffId"]);
        }

        try {
            if (
                in_array($node, AbstractWorkflow::getForkStartNodeList($workflow))
            ){
                if (!$afterForkedNodesFinishedNextJunctionNodeAssignedStaffId) {
                    throw new \RuntimeException("ForkStartNode 必须指定 afterForkedNodesFinishedNextJunctionNodeAssignedStaffId");
                }
                $currentNodeAssignment->setAfterForkedNodesFinishedNextJunctionNodeAssignedStaffId($afterForkedNodesFinishedNextJunctionNodeAssignedStaffId);
            }

            $currentNodeAssignment->setNodeStatus(NodeAssignment::STATUS_FINISHED)
                ->setFinishedTime(time())
                ->setSubmittedData($data);
            $this->nodeAssignmentRepository->storeNodeAssignment($currentNodeAssignment);
        } catch (OptimisticLockException $e) {
            //todo 外面如果catch到error code为10002就重新rollback ， 再refresh  **currentNodeAssignment** 后重试
            //我抛异常rollback 退出业务逻辑，重新随机sleep几秒后进行事务处理这个当前方法.
            throw new \RuntimeException("currentNodeAssignment outdated", 10002, $e);
        }



        if (
            in_array($node, AbstractWorkflow::getNormalOneNextNodeList($workflow))
        ) {
            if (count($nextNodeDesc) !== 1) {
                throw new \RuntimeException("普通流程节点不能有多分支");
            }
            $perNextNodeDesc =$nextNodeDesc[0];
            //普通节点与汇聚节点 需要分配 nextNodeDesc
            // 对nextNodeDesc中的人创建nodeAssignment
            /** @var string $nextNode */
            $nextNode = $perNextNodeDesc["nextNode"];
            /** @var string $assignedStaffId */
            $assignedStaffId = $perNextNodeDesc["assignedStaffId"];
            $newNodeAssignment = new NodeAssignment();

            $this->checkNextNodeIsAllowed($node, $nextNode);

            $workflow->setCurrentFinishedNode($node)
                ->setCurrentAssignedStaffId($currentStaffId)
            ;

            if ($nextNode === "END") {
                $workflow->setFinishedTime(time());
            } else {
                $newNodeAssignment->setAssignedNode($nextNode)
                    ->setAssignedStaffId($assignedStaffId)
                    ->setAssignedTime(time())

                    ->setIsWorkflowLauncher(false)
                    ->setNodeStatus(NodeAssignment::STATUS_NEW)
                    ->setShopId($workflow->getShopId())
                    ->setSubmittedData(null)
                    ->setWorkflowClassName(get_class($workflow))
                    ->setWorkflowId($workflow->getId())
                    ->setPrevNodeAssignmentId($currentNodeAssignment->getId())
                ;
                $this->nodeAssignmentRepository->storeNodeAssignment($currentNodeAssignment);
            }

            $this->entityManager->persist($workflow);
            $this->entityManager->flush($workflow);
        }

        if (
            in_array($node, AbstractWorkflow::getForkStartNodeList($workflow))
        ) {
            $workflow->setCurrentFinishedNode($node)
                ->setCurrentAssignedStaffId($currentStaffId)
            ;
            $this->entityManager->persist($workflow);
            $this->entityManager->flush($workflow);

            //分叉开始节点 需要分配 nextNodeDesc 与 afterForkedNodesFinishedNextJunctionNodeAssignedStaffId，
            //对nextNodeDesc中的人创建nodeAssignment，
            foreach ($nextNodeDesc as $perNextNodeDesc) {
                /** @var string $nextNode */
                $nextNode = $perNextNodeDesc["nextNode"];

                $this->checkNextNodeIsAllowed($node, $nextNode);

                /** @var string $assignedStaffId */
                $assignedStaffId = $perNextNodeDesc["assignedStaffId"];
                $newNodeAssignment = new NodeAssignment();

                $newNodeAssignment->setAssignedNode($nextNode)
                    ->setAssignedStaffId($assignedStaffId)
                    ->setAssignedTime(time())

                    ->setIsWorkflowLauncher(false)
                    ->setNodeStatus(NodeAssignment::STATUS_NEW)
                    ->setShopId($workflow->getShopId())
                    ->setSubmittedData(null)
                    ->setWorkflowClassName(get_class($workflow))
                    ->setWorkflowId($workflow->getId())
                    ->setPrevNodeAssignmentId($currentNodeAssignment->getId())
                ;
                $this->nodeAssignmentRepository->storeNodeAssignment($currentNodeAssignment);
            }
        }

        if (
        in_array($node, AbstractWorkflow::getInForkingNodeList($workflow))
        ) {
            //分叉中节点, 需要判断当前分叉类型  与 兄弟分叉节点是否完成，来判断是否给
            // 上一个分叉开始节点的afterForkedNodesFinishedNextJunctionNodeAssignedStaffId
            // 来分配 创建nodeAssignment，

            $prevNodeAssignmentId = $currentNodeAssignment->getPrevNodeAssignmentId();
            if (!$prevNodeAssignmentId) {
                throw new \RuntimeException("分叉中的节点 prevNodeAssignmentId 不能为空");
            }
            /** @var NodeAssignment[] $allSiblingNodeAssignmentList */
            $allSiblingNodeAssignmentList = $this->nodeAssignmentRepository->findBy([
                "prevNodeAssignmentId" => $prevNodeAssignmentId,
            ],["id"=>"DESC"],99999);

            $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = null;
            $prevNodeAssignment = null;
            $currentInForkingNodeType = AbstractWorkflow::getInForkingNodeType($workflow, $node);
            if (in_array($currentInForkingNodeType,["&","&n"])) {
                //获取所有分叉兄弟节点 是否都已经完成，如果都完成了 就给afterForkedNodesFinishedNextJunctionNodeAssignedStaffId分配 创建nodeAssignment，
                $isAllSiblingNodeAssignmentFinished = true;
                foreach ($allSiblingNodeAssignmentList as $perSiblingNodeAssignment) {
                    if ($perSiblingNodeAssignment->getId() !== $currentNodeAssignment->getId()) {
                        if ($perSiblingNodeAssignment->getNodeStatus() !== NodeAssignment::STATUS_FINISHED) {
                            $isAllSiblingNodeAssignmentFinished = false;
                            break;
                        }
                    }
                }
                if ($isAllSiblingNodeAssignmentFinished) {
                    $prevNodeAssignment = $this->nodeAssignmentRepository->find($prevNodeAssignmentId);
                    $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = $prevNodeAssignment->getAfterForkedNodesFinishedNextJunctionNodeAssignedStaffId();
                }
            }

            if (in_array($currentInForkingNodeType,["|","|n"])) {
                //获取所有分叉兄弟节点，把他们都改成状态4 表示["|nF"]时兄弟节点已经处理进入下一步本节点已经结束
                foreach ($allSiblingNodeAssignmentList as $perSiblingNodeAssignment) {
                    if ($perSiblingNodeAssignment->getId() !== $currentNodeAssignment->getId()) {
                        $perSiblingNodeAssignment->setNodeStatus(NodeAssignment::STATUS_OBSOLETE_FOR_SIBLING_IN_FORKING_NODE_FINISHED);
                        $this->entityManager->persist($perSiblingNodeAssignment);
                    }
                }

                try{
                    $this->entityManager->flush();
                } catch (OptimisticLockException $e) {
                    // todo flush保存他们到数据库，触发乐观锁逻辑，我抛异常rollback 退出业务逻辑，重新随机sleep几秒后进行事务处理这个当前方法 外面如果catch到error code为10003就重新rollback ， 再refresh  **currentNodeAssignment** 后重试
                    //我抛异常rollback 退出业务逻辑，重新随机sleep几秒后进行事务处理这个当前方法.
                    throw new \RuntimeException("currentNodeAssignment outdated", 10003, $e);
                }


                $prevNodeAssignment = $this->nodeAssignmentRepository->find($prevNodeAssignmentId);
                $afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = $prevNodeAssignment->getAfterForkedNodesFinishedNextJunctionNodeAssignedStaffId();
            }

            //给afterForkedNodesFinishedNextJunctionNodeAssignedStaffId分配 创建nodeAssignment，这个nodeAssignMent的PrevId是当前我这个nodeAssignmentId，
            if ($afterForkedNodesFinishedNextJunctionNodeAssignedStaffId) {
                $newNodeAssignment = new NodeAssignment();

                $nextNode = AbstractWorkflow::getJunctionNodeAfterForkStartNodeType($workflow, $prevNodeAssignment->getAssignedNode());
                $newNodeAssignment->setAssignedNode($nextNode)
                    ->setAssignedStaffId($afterForkedNodesFinishedNextJunctionNodeAssignedStaffId)
                    ->setAssignedTime(time())
                    ->setIsWorkflowLauncher(false)
                    ->setNodeStatus(NodeAssignment::STATUS_NEW)
                    ->setShopId($workflow->getShopId())
                    ->setSubmittedData(null)
                    ->setWorkflowClassName(get_class($workflow))
                    ->setWorkflowId($workflow->getId())
                    ->setPrevNodeAssignmentId($currentNodeAssignment->getId())
                ;
                $this->nodeAssignmentRepository->storeNodeAssignment($currentNodeAssignment);

                //为了解决由于并发问题，导致创建多个汇聚节点，规定id比当前汇聚节点小的汇聚节点的assignedTime 与 当前时间必须要大于10秒，否则就删掉这个汇聚节点
                $mayNotAllowNodeAssignmentList = $this->nodeAssignmentRepository->getPrevSimilarNodeAssignmentList($newNodeAssignment);
                if ($mayNotAllowNodeAssignmentList) {
                    foreach ($mayNotAllowNodeAssignmentList as $mayNotAllowNodeAssignment) {
                        $this->entityManager->remove($mayNotAllowNodeAssignment);
                    }
                    $this->entityManager->flush();
                }
            }
        }

    }

    private function checkNextNodeIsAllowed($currentNode, $nextNode)
    {
        //must todo
    }

}