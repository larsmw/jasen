import java.net.*;
import java.io.*;

public class LinkWorker
{
   public static void main(String [] args)
   {
      String serverName = args[0];
      int port = Integer.parseInt(args[1]);
      try
      {
         System.out.println("Connecting to " + serverName
                             + " on port " + port);
         Socket client = new Socket(serverName, port);
         System.out.println("Just connected to "
                      + client.getRemoteSocketAddress());
         OutputStream outToServer = client.getOutputStream();
         ObjectOutputStream out =
                       new ObjectOutputStream(outToServer);

	 Data data = new Data();
	 data.url = "http://lfw.dk/";
	 data.links[0] = "http://docs.oracle.com/javase/tutorial/java/nutsandbolts/arrays.html";

         out.writeObject(data);
	 /*+"Hello from "
	   + client.getLocalSocketAddress());*/
         InputStream inFromServer = client.getInputStream();
         DataInputStream in =
                        new DataInputStream(inFromServer);
         System.out.println("Server says " + in.readUTF());
         client.close();
      } catch (FileNotFoundException ex) {
	  ex.printStackTrace();
      }catch(IOException e)
	  {
	      e.printStackTrace();
	  }
   }
}
