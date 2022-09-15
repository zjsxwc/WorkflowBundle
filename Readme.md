## php工作流设计 ##

1）一个大的doctrine entity class，每个field字段就是 完整流程里面所有字段，这个class也就是数据库表的schema，
这个类里有一个方法nodeTransitions()返回所有node列表，node如何转换到下一个node，返回结构如下：
[
    "nodeList" => ["A", "B", "C", "D", "E", "F", "G", "H", "I","J","K","X", "Y","Z","W"],//这里A、B等可以用多个字中文表示
    "transitions" => [
            "A" => "B",
            "B" => ["C", "D"],//B 同时分叉并行 C 与 D， C与D是临时状态, 所有分支临时节点都不能处理时指派下一节点处理人，得由分叉上一级节点比如这里的B来指派C与D分叉提交后的处理人,
                             //分叉节点的下一个节点不允许继续分叉 必须聚合到一个节点处理，
                             //后期 分叉节点 可以 关联发起一个 workflow（由workflow记录这个分叉nodeAssignment的id，这个分叉的状态变成 虽然提交了 但未完成 pending）， 当workflow 完成 这个分叉节点也完成，来实现分叉的分叉的效果                 
            "C&D" => "E", //C与D全都提交了 就会进入E
            "E" => "X",
            "X" => ["&nY"],//&n开头表示 n 个 Y 并行都提交后完成 &nY，这种时候Y节点下的字段都为json数组存储格式： [{"staffId":"张三","value":"xxxx"},{{"staffId":"李四","value":"yyyy"}}] 
            "&nY" => "Z",
            "Z" => ["|nF"], //&n开头表示 n 个 Y 并行其中有一个提交后完成 |nF，未处理的分叉节点不能继续提交，未处理的分叉节点状态变成兄弟节点已经处理完成。
            "|nF" => "W",
            "W" => ["G", "H"],
            "G|H" => "I",//G或H有一个提交 就会进入E
            "I" => "J|END",//表示I可以直接结束工作流，也可以继续到节点J，END是默认完成工作流
            "J" => "K",
            "K" => "I" //实现节点之间循环
    ]
]

一个次完整的流程，我们称它为一次 Workflow，所以这个entity class叫做XxxWorkflow， Workflow需要加乐观锁。

2）给每个字段添加 “node名字+R/W”  的 php8 attribute，表示这个字段会在对应的node节点出现让用户展示或填写，
每个字段可以有多个attribute，如果这个字段没有任何attribute就不可能出现在任何node节点上，让用户看到。

2.0)为了嵌入丰云汇店员系统，登录人为店员，每个店铺都有独立工作流，所以工作流需要 记录shopId，
工作流要有一个 唯一的 中文 workflowName
工作流要有一个 字段 currentFinishedNode 表示当前处于哪个已经完成的Node，也就是transitions 数组的key，多分支时使用 "C&D" "G|H" 来表示
对于已经结束的工作流 要记录 完成时间 finishedTime
工作流的assignedStaffId 直接使用 字符串 店员的username也就是中文名，
工作流要记录 发起者 launchedStaffId，
节点的发起者与非发起者都有一个entity  nodeAssignment 类 辅助记录 
nodeAssignment {
    id
    prevNodeAssignmentId
    shopId
    assignedStaffId
    workflowId
    workflowClassName (完整的类名)
    assignedNode
    nodeStatus  （待处理、已完成处理）
    assignedTime
    isWorkflowLauncher
    afterForkedNodesFinishedNextJunctionNodeAssignedStaffId (nullable)
    finished_time
    submittedData （nullable）
}
nodeAssignment 也需要加乐观锁 防止在分叉分支中被修改

2.1) 如果是["&nY"]并行分支的字段，这个字段就必须是json的array的text 存储格式： [{"staffId":"张三","value":"xxxx"},{{"staffId":"李四","value":"yyyy"}}]

2.2) 由于项目是php7，不支持php8 attribute，所以类里有一个方法fieldNodeAttributes()返回所有field的node Attribute：

fieldNodeAttributes  = [
   "fieldName1" => [
       "displayName" => "中文描述名",
       "nodeAttributes" => [
           "B+R",
           "C+W",
           "D+RW",
       ]
   ]
];


@ORM\Column(name="area_calculations", type="json_array")

格式 [
  ["staffId"=> 123, "value" => []],
  ["staffId"=> 345, "value" => []],
]

3）每个node都有一个对应的html文件，这个html文件可以在渲染时，
给window创建全局变量fieldList来 获取（2）所设置attrbute的当前node页面字段名列表与查看修改权限，
然后这个html提交包含这些field值的表单。
也可以通过json获取 全局变量fieldList

4）这个提交的表单也包含一个字段nextNodeDesc 当前表单代表的节点node要 转换 到的下一个节点以及节点执行人ID,结构如下：

//如果是处于分支节点就不需要指派nextNodeDesc，因为上一个分配分支的节点用afterForkedNodesFinishedNextJunctionNodeAssignedStaffId指派了
nextNodeDesc=[
{nextNode: "C", assignedStaffId: 123},//表示对员工id为123的执行人，分配填写本次工作流程下节点C的表单
{nextNode: "D", assignedStaffId: 456},
]
//如果是分配分支的节点就还需要指派 分支的聚合节点人
afterForkedNodesFinishedNextJunctionNodeAssignedStaffId = 123

5）执行人被分配node节点任务，就能收到email通知，来处理node表单。

6）员工可以发起工作流，员工选择工作流后就进入 nodeTransitions() 返回的nodeList第一个节点的填写环节，一旦提交这个节点就完成（表明工作流也先创建了），进入下一个节点，
如果不提交也不会创建工作流。

7）员工可以查看他发起的工作流、可以查看他参与的（非发起的）别人的node节点[只看到他当时节点能看到的与他自己填写的]


8）api接口设计

GET /workflow/{shopId}/process/{workflowClassName}/{launcherNode}?copyFromWorkflowId=
GET /workflow/{shopId}/process/{workflowClassName}/{node}/{nodeAssignmentId}
如果是json请求 就返回 这个node中表单填写时需要用到的 全局变量fieldList
如果是html 就 渲染  全局变量fieldList  构建表单

copyFromWorkflowId表示能够复制初始节点数据


POST /workflow/{shopId}/process/{workflowClassName}/{launcherNode}
POST /workflow/{shopId}/process/{workflowClassName}/{node}/{nodeAssignmentId}

提交 全局变量fieldList 中需要写W的字段数据
提交 下一个节点被分配员工的id
如果下面节点是分叉节点 则每个分叉节点都要分配员工的id,同时提交 聚合节点被分配员工的id


GET /workflow/{shopId}/assigned-nodes?status=0,1,2,3,4

获取被分配的节点,status=0表示待处理 status=1表示已经处理完成 status=2表示已经回退到上一个节点当前节点被废弃 
 status=3表示已经整个工作流所有节点被废弃也就是工作流本身也被废除，4表示["|nF"]时兄弟节点已经处理进入下一步本节点已经结束。


GET /workflow/{shopId}/launched-nodes

获取当前用户发起的工作流


9）创建一个bundle，这个bundle监听request事件，如果是/workflow开头就必须要店员assistence登录才能进来，所有workflow代码都在这个bundle下面，
post提交node数据时 相关下个店员指派都写死在页面里面，但必须在对应的店铺下创建店员


10)一个核心Processor类来处理 workflowClass  与  nodeAssignment，processor每次修改workflow的一行数据都要 for update 上排他所获取，避免分叉节点更新同一个数组字段导致数据丢失。

11）对node图结构生成html预览

12）对完成后的工作流进行报表导出excel由线下人员进行office数据处理，由有“商户工作流管理”角色的人拥有报表导出按钮（使用单元格右上角批注数据填写人，["&nY"]数据则向下空行），时间跨度最多3个月。
或 报表html浏览

13）NodeAssignmentRepository 提供 获取当前InForkingNode节点 对应的NodeAssignment 的所有兄弟分支节点 的NodeAssignment

14) 为了解决由于并发问题，导致创建多个汇聚节点，规定id比当前汇聚节点小的汇聚节点的assignedTime 与 当前时间必须要大于10秒，否则就删掉这个汇聚节点