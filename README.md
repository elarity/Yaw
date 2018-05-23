# Yaw的综述：
Yaw : Yet Another Workerman。

用纯C语言做些东西还是很难的，这辈子都不会用C语言的，Java又不会，就是写PHP这种东西才能维持生活这样子，一进php群感觉就像回家一样，里面个个都是人才，说话又好听，我超喜欢里面的，只能用php写个山寨版的workerman了。

# 进程模型：
Yaw的进程模型会介于Workerman和Swoole之间，会比Workerman多一组进程，比Swoole少一些进程。本来我是打算按照Swoole的文档和Swoole的进程模型“逆向”出Yaw来，但是由于Swoole的Master进程中用了非常屌的Reactor线程，这个我是很难实现了，所以，照抄Swoole的进程模型是很难了。


# 黄牌⚠️：
还没写完呢昂！我现在仅仅是把每天写的东西提交到github上，并不是说这个东西已经完成一个基本可用的版本了。哪天说这东西凑合能用了，我会修改README的
