<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>web掲示板</title>
</head>
<body>
    <h1><font color="royalblue">WEB掲示板</font></h1>
    
    <?php

////////　データベースの接続設定　////////
        $dsn = 'mysql:dbname=データベース名;host=localhost';
        $user = 'ユーザー名';
        $password = 'パスワード';
        $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

////////　テーブルの作成　////////
        $sql = "CREATE TABLE IF NOT EXISTS boardtb"
        ." ("
        . "id INT AUTO_INCREMENT PRIMARY KEY,"  //投稿番号　整数しか入らない　１ずつ増加　重複しない
        . "name char(32),"  //名前　文字列
        . "comment TEXT,"  //コメントなのでテキスト（65,535バイトまで）
        . "daytime char(25),"  //投稿日時　/や：も入るため文字列　絶対に20文字しか入らないが、念のため多めに25文字
        . "pass char(20)"  //パスワード　文字列　20文字以内
        .");";
        $stmt = $pdo->query($sql);

////////　投稿フォーム　////////

    ////////　編集　////////
        //投稿フォームの 編集対象番号、名前、コメント の入力欄に値が入っている場合、以下の処理を実行    
        if( !empty($_POST["editing_num"]) && !empty($_POST["str_name"]) && !empty($_POST["str_comment"])  ){
            //受信した内容をそれぞれ変数に代入
            $name=$_POST["str_name"];
            $comment=$_POST["str_comment"];
            $editing_num=$_POST["editing_num"];
            $pass=$_POST["password_c"];
            //現在の日時を変数に代入
            $day=date("Y/m/d H:i:s");

            //編集対象番号と id が一致するレコードを、投稿フォームに入力された 名前、コメント、パスワード へ更新する　日時は現在日時へ更新する
            $sql = "UPDATE boardtb SET name=:name,comment=:comment,pass=:pass,daytime=:daytime WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
            $stmt->bindParam(':pass', $pass, PDO::PARAM_STR);
            $stmt->bindParam(':daytime', $day, PDO::PARAM_STR);
            $stmt->bindParam(':id', $editing_num, PDO::PARAM_INT);
            $stmt->execute();
        }
        
    ////////　新規投稿　////////
        //投稿フォームに名前とコメントの両方の入力欄に値が入っていて、編集対象番号の入力欄には値が入っていない場合、以下の処理を実行    
        if( !empty($_POST["str_name"]) && !empty($_POST["str_comment"]) && empty($_POST["editing_num"]) ){
            //受信した内容を変数に代入
            $name=$_POST["str_name"];
            $comment=$_POST["str_comment"];
            $pass=$_POST["password_c"];
            //現在の日時を変数に代入
            $day=date("Y/m/d H:i:s");

            //入力された内容を、データレコードで追加　　※idは勝手に入るので入れなくていい
            $sql = $pdo -> prepare("INSERT INTO boardtb (name, comment, daytime, pass) VALUES (:name, :comment, :daytime, :pass)");
            $sql -> bindParam(':name', $name, PDO::PARAM_STR);    //パラメータは変数のみ、入るデータは文字列　以下同じ
            $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
            $sql -> bindParam(':daytime', $day, PDO::PARAM_STR);
            $sql -> bindParam(':pass', $pass, PDO::PARAM_STR);
            $sql -> execute();
        }

////////　削除フォーム　////////
        
        //削除対象番号とパスワードの入力欄に値が入っている場合、以下の処理を実行    
        if(!empty($_POST["delete"]) && !empty($_POST["password_d"]) ){
          
            //削除対象番号と入力されたパスワードを受信、変数に代入
            $delete=$_POST["delete"] ;
            $pass=$_POST["password_d"] ;
          
          //送信された内容に対する結果を表示させる  　※３パターン：削除対象番号の投稿がそもそも存在しない or パスワードが設定されていない or パスワードが間違っている
            //削除対象番号とidが一致したレコードを抽出
            $sql = "SELECT * FROM boardtb WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":id", $delete, PDO::PARAM_INT);
            $stmt->execute();
            //削除対象番号のレコードを配列として変数へ代入
            $results = $stmt->fetch();
            //指定した投稿がない場合
            if(empty($results) ){
                echo '<font color="red">指定した投稿番号は、ありません</font><br>';
            //パスワードが設定されてない（""）場合
            }elseif($results['pass']==""){
                echo '<font color="red">パスワードが設定されていない投稿は、削除できません</font><br>';
            //入力されたパスワードが間違っている場合
            }elseif($pass!=$results['pass'] ){
                echo '<font color="red">パスワードが違います</font><br>';
            }
            
          //削除機能本体  
            //deletで削除対象番号とidが一致したレコードを削除する
            $sql = "DELETE from boardtb where id=:id AND pass=:pass AND pass!='' ";    //idのカラムで削除対象番号と等しい場合、そのレコードを削除する　また、パスワードがNULLじゃない場合　　※ where pass IS NOT NULL ではできなかった。なぜか pass に " " （半角スペース1文字）が入っていてNULLじゃない
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $delete, PDO::PARAM_INT);
            $stmt->bindParam(':pass', $pass, PDO::PARAM_STR);
            $stmt->execute();
        }

////////　編集フォーム　////////
        
      //編集したい投稿を抽出、投稿フォーム内へ表示
        //編集フォームの編集対象番号とパスワードの入力欄に値が入っている場合、以下の処理を実行 
        if(!empty($_POST["edit"]) && !empty($_POST["password_e"]) ){
            //編集フォームに入力された編集対象番号を $edit_n へ、パズワードを $pass に代入
            $edit_n=$_POST["edit"];
            $pass=$_POST["password_e"];

          //SELECTで編集対象番号とidが一致したレコードのname、comment、passを抽出
            //SELECTのSQL文をセット　編集対象番号とid、入力したパスワードとpassが一致したレコードを抽出　また、passがあるレコードが対象
            $sql = "SELECT * FROM boardtb WHERE id=:id AND pass=:pass AND pass!='' ";
            //DBを指定、準備
            $stmt = $pdo->prepare($sql);
            //パラメータをセット
            $stmt->bindParam(":id", $edit_n, PDO::PARAM_INT);
            $stmt->bindParam(":pass", $pass, PDO::PARAM_STR);
            //SQL実行
            $stmt->execute();

          //抽出したレコードをカラムごとに変数へ代入
            //抽出したレコードを配列として変数へ代入　　※このとき $results は１配列で、５×2つの要素が入っている array(10){ ["id"][0]["name"][1]["comment"][2]["daytime"][3]["pass"][4] }（要素の中身は省略）
            $results = $stmt->fetch();    //１行のレコードしか抽出しないから fetchAll ではなく、fetch を使う
            //配列の各要素をそれぞれの変数へ代入
            $edit_num = $results["id"];
            $name_data = $results["name"];
            $comment_data = $results["comment"];
            $password_data = $results["pass"];
            
          //送信された内容に対する結果を表示させる  　※３パターン：編集対象番号の投稿がそもそも存在しない or パスワードが設定されていない or パスワードが間違っている
            //編集対象番号とidが一致したレコードを抽出
            $sql = "SELECT * FROM boardtb WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":id", $edit_n, PDO::PARAM_INT);
            $stmt->execute();
            //編集対象番号のレコードを配列として変数へ代入
            $results = $stmt->fetch();
            //指定した投稿がない場合
            if(empty($results) ){
                echo '<font color="red">指定した投稿番号は、ありません</font><br>';
            //パスワードが設定されてない（""）場合
            }elseif($results['pass']==""){
                echo '<font color="red">パスワードが設定されていない投稿は、編集できません</font><br>';
            //入力されたパスワードが間違っている場合
            }elseif($pass!=$results['pass'] ){
                echo '<font color="red">パスワードが違います</font><br>';
            }  
        }
    ?>

<!--　投稿フォーム　-->
    
    <?php if(!empty($edit_num) ){echo "<strong>【". $edit_num. "番の投稿を編集中】</strong>";} //投稿フォームが編集を実行している（隠したテキストボックスに値が入っている）ときに編集中の投稿番号を表示させる ?>
    <form action = "" method="post">
        <?php if(empty($edit_num)){echo "<strong>【新規投稿フォーム】</strong><br>";} //編集中じゃないときは「新規投稿フォーム」と表示させる ?>
        <input type="hidden" name="editing_num" placeholder="編集対象番号" value="<?php if(!empty($edit_num)){echo $edit_num;} ?>">
        <input type="txst" name="str_name" placeholder="名前" title="名前 を入力してください" value="<?php if(!empty($name_data)){echo $name_data;} ?>">
        <input type="txst" name="str_comment" placeholder="コメント" title="コメント を入力してください" value="<?php if(!empty($comment_data)){echo $comment_data;} ?>">
        <input type="password" name="password_c" placeholder="パスワード" pattern=".*\S+.*" title="パスワード を20文字以内で入力してください。また、スペースのみで入力しないでください。" value="<?php if(!empty($password_data)){echo $password_data;} ?>">
        　<input type="submit" name="submit" value=<?php if(empty($edit_num)){echo "投稿";} else {echo "編集実行";} ?>>
    </form><br>

<!--　削除フォーム　-->
    
    <form action = "" method="post">
        <strong>【削除フォーム】</strong><br>
        <input type="number" name="delete" placeholder="削除したい番号を入力">
        <input type="password" name="password_d" placeholder="パスワード">
        　　　　　　　 　 　　
        　<input type="submit" name="submit_d" value="削除">
    </form><br>
    
<!--　編集フォーム　-->
    
    <form action = "" method="post">
        <strong>【編集フォーム】</strong><br>
        <input type="number" name="edit" placeholder="編集したい番号を入力">
        <input type="password" name="password_e" placeholder="パスワード">
        　　　　　　　 　 　　
        　<input type="submit" name="submit_e" value="編集">
    </form>

<!--　投稿内容表示　-->
    <br><br>
    <strong>【投稿内容】</strong><br>
    <hr>
    <?php 

////////　投稿内容を表示させる　////////
        
      //テーブルの中身を全て表示させる
        //テーブルの全フィールドを抽出
        $sql = 'SELECT * FROM boardtb';
        $stmt = $pdo->query($sql);

        //抽出した全フィールドを、レコードごとに１配列として、全配列を変数へ代入
        $results = $stmt->fetchAll();
        //１配列を１つの変数に代入 を全ての配列に反復処理　　※ $rowの中には各レコードが配列で入る　
        foreach ($results as $row){
            //1つのレコードをid、name、comment、daytimeの順で表示
            echo '<strong>'. $row['id'] .'</strong>'.':';
            echo '<span style="background-color:rgba(169, 205, 255, 0.589)">'. $row['name']. '</span>'. ' ';
            echo $row['comment'];
            echo '<font color="gray">（'. $row['daytime'] .'）</font><br>';
            
        }

    ?>
  
</body>
</html>