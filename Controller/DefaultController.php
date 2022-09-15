<?php

namespace WorkflowBundle\Controller;

use Common\Util\NeedJsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use UserBundle\Util\SymfonySecurityFirewallUser;
use WorkflowBundle\Entity\AbstractWorkflow;
use WorkflowBundle\Entity\NodeAssignment;
use WorkflowBundle\Util\WorkflowProcessor;

/**
 * @Route("/workflow")
 */
class DefaultController extends Controller
{

    /**
     * //fixme 这个方法是我这个项目里把商户id作为NodeAssignment的shopId判断 ，如果是别的项目，请根据你项目的实际情况处理
     */
    protected function validUserNodeAssignmentShopId(NodeAssignment $nodeAssignment)
    {
        /** @var SymfonySecurityFirewallUser $user */
        $user = $this->getUser();
        if ($nodeAssignment->getShopId() !== $user->getAccountId()) {
            throw new \RuntimeException("nodeAssignmentId 无效 shopId");
        }
    }

    /**
     * //fixme 这个方法是我这个项目里把商户的店员的username当作 工作流的 staffId，如果是别的项目，请根据你项目的实际情况返回
     * @return string
     */
    protected function getCurrentStaffId()
    {
        /** @var SymfonySecurityFirewallUser $user */
        $user = $this->getUser();
        $currentStaffId = $user->getShopAssistant()->getUsername();
        return $currentStaffId;
    }

    protected function shouldJsonResponse(Request $request)
    {
        //fixme 这个方法是我自己业务里判断是否需要json返回，如果是别的项目，请根据你项目的实际情况返回，参考下面的注释返回
        return NeedJsonResponse::check($request);
//        $isNeedJson = false;
//        if ($request->isXmlHttpRequest()) {
//            $isNeedJson = true;
//        }
//
//        if (isset($_COOKIE["isNeedJson"]) && $_COOKIE["isNeedJson"]) {
//            $isNeedJson = true;
//        }
//
//        $isNeedJsonParam = $request->query->getBoolean("isNeedJson");
//        if ($isNeedJsonParam) {
//            $isNeedJson = true;
//        }
//
//        return $isNeedJson;
    }

    /**
     * @Route("/{shopId}/edit", name="xxxxxcc")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($shopId)
    {
        dump($shopId, $this->getUser());
        return $this->render('WorkflowBundle:Default:index.html.twig');
    }

    /**
     * @Route("/{shopId}/process/{workflowClassName}/{node}/{nodeAssignmentId}", name="process_workflow_node")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function processNodeAction(Request $request, $shopId, $workflowClassName, $node, $nodeAssignmentId)
    {
        $shopId = intval($shopId);

        $fullWorkflowClassName = "WorkflowBundle\\Entity\\" . $workflowClassName;
        if (!class_exists($fullWorkflowClassName)) {
            throw new \RuntimeException("$workflowClassName 这个类不存在");
        }

        $currentStaffId = $this->getCurrentStaffId();
        if (!is_numeric($nodeAssignmentId)) {
            throw new \RuntimeException("nodeAssignmentId 无效");
        }
        $nodeAssignmentId = intval($nodeAssignmentId);
        $nodeAssignmentRepo = $this->getDoctrine()->getRepository("WorkflowBundle:NodeAssignment");
        /** @var NodeAssignment $nodeAssignment */
        $nodeAssignment = $nodeAssignmentRepo->find($nodeAssignmentId);
        if (!$nodeAssignment) {
            throw new \RuntimeException("nodeAssignment 无效");
        }
        if ($nodeAssignment) {
            if ($nodeAssignment->getAssignedNode() !== $node) {
                throw new \RuntimeException("nodeAssignment 无效 AssignedNode");
            }
            if ($nodeAssignment->getAssignedStaffId() !== $currentStaffId) {
                throw new \RuntimeException("nodeAssignment 无效 AssignedStaffId");
            }
            if ($nodeAssignment->getWorkflowClassName() !== $fullWorkflowClassName) {
                throw new \RuntimeException("nodeAssignment 无效 WorkflowClassName");
            }
            if ($nodeAssignment->getNodeStatus() !== NodeAssignment::STATUS_NEW) {
                throw new \RuntimeException("nodeAssignment 已经不能被处理");
            }
            if ($nodeAssignment->getShopId() !== $shopId) {
                throw new \RuntimeException("nodeAssignment 无效 ShopId");
            }
            $this->validUserNodeAssignmentShopId($nodeAssignment);
        }


        $workflowRepo = $this->getDoctrine()->getRepository("WorkflowBundle:".$workflowClassName);
        /** @var AbstractWorkflow $workflow */
        $workflow = $workflowRepo->find($nodeAssignment->getWorkflowId());
        if (!$workflow) {
            throw new \RuntimeException("nodeAssignment 无效 WorkflowId");
        }
        $validLauncherNode = AbstractWorkflow::getLauncherNode($workflow);
        if ($node !== $validLauncherNode) {
            throw new \RuntimeException("请求的 $node 是当前工作流 $workflowClassName 的有效起始节点，这里不允许起始节点");
        }

        /** @var WorkflowProcessor $processor */
        $processor = $this->get("workflow.processor");

        if ($request->isMethod("POST")) {
            $dataStr = $request->request->get("data");
            $data = json_decode($dataStr, true);
            $currentNodeAssignment = $processor->processNodeSubmittedData($workflow, $node, $data, $currentStaffId, $nodeAssignment);
            return $this->json([
                "code" => -1,
                "currentNodeAssignment" => $currentNodeAssignment
            ]);
        } else {
            $nodeFieldDataAttributesList = $processor->getNodeFieldDataAttributesList($workflow, $node);
            $dataParams = [
                "currentStaffId" => $currentStaffId,
                "workflowName" => $workflow->getWorkflowName(),
                "workflowDesc" => $workflow->getWorkflowDesc(),
                "workflowClassName" => $workflowClassName,
                "node" => $node,
                "nodeFieldDataAttributesList" => $nodeFieldDataAttributesList
            ];
            if ($this->shouldJsonResponse($request)) {
                return $this->json([
                    "code" => -1,
                    "data" => $dataParams
                ]);
            } else{
                return $this->render('WorkflowBundle:'.$workflow->getWorkflowName().':'.$node.".html.twig");
            }
        }
    }

    /**
     * @Route("/{shopId}/process/{workflowClassName}/{launcherNode}", name="process_workflow_launcher_node")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function processLauncherNodeAction(Request $request, $shopId, $workflowClassName, $launcherNode)
    {
        $shopId = intval($shopId);

        $fullWorkflowClassName = "WorkflowBundle\\Entity\\" . $workflowClassName;
        if (!class_exists($fullWorkflowClassName)) {
            throw new \RuntimeException("$workflowClassName 这个类不存在");
        }
        /** @var AbstractWorkflow $workflow */
        $workflow = new $fullWorkflowClassName();
        if (!($workflow instanceof AbstractWorkflow)) {
            throw new \RuntimeException("存在注入php的危险");
        }
        $workflow->setShopId($shopId);
        $validLauncherNode = AbstractWorkflow::getLauncherNode($workflow);
        if ($launcherNode !== $validLauncherNode) {
            throw new \RuntimeException("请求的 $launcherNode 不是当前工作流 $workflowClassName 的有效起始节点");
        }
        /** @var WorkflowProcessor $processor */
        $processor = $this->get("workflow.processor");
        $currentStaffId = $this->getCurrentStaffId();
        if ($request->isMethod("POST")) {
            $dataStr = $request->request->get("data");
            $data = json_decode($dataStr, true);
            $currentNodeAssignment = $processor->processNodeSubmittedData($workflow, $launcherNode, $data, $currentStaffId, null);
            return $this->json([
                "code" => -1,
                "currentNodeAssignment" => $currentNodeAssignment
            ]);
        } else {
            $nodeFieldDataAttributesList = $processor->getNodeFieldDataAttributesList($workflow, $launcherNode);
            $dataParams = [
                "currentStaffId" => $currentStaffId,
                "workflowName" => $workflow->getWorkflowName(),
                "workflowDesc" => $workflow->getWorkflowDesc(),
                "workflowClassName" => $workflowClassName,
                "node" => $launcherNode,
                "nodeFieldDataAttributesList" => $nodeFieldDataAttributesList
            ];
            if ($this->shouldJsonResponse($request)) {
                return $this->json([
                    "code" => -1,
                    "data" => $dataParams
                ]);
            } else{
                return $this->render('WorkflowBundle:'.$workflow->getWorkflowName().':'.$launcherNode.".html.twig");
            }
        }

    }

}
