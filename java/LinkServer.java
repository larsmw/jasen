
import java.net.*;
import java.io.*;

public class LinkServer extends Thread
{
   private ServerSocket serverSocket;
   
   public LinkServer(int port) throws IOException
   {
      serverSocket = new ServerSocket(port);
      serverSocket.setSoTimeout(3600000);
   }

   public void run()
   {
       while(true)
	   {
	       try
		   {
		       System.out.println("Waiting for client on port " +
					  serverSocket.getLocalPort() + "...");
		       Socket server = serverSocket.accept();
		       System.out.println("Just connected to "
					  + server.getRemoteSocketAddress());
		       DataInputStream in =
			   new DataInputStream(server.getInputStream());
		       ObjectInputStream obj = new ObjectInputStream(in);
		       System.out.println(obj.readObject().toString());
		       DataOutputStream out =
			   new DataOutputStream(server.getOutputStream());
		       out.writeUTF("Thank you for connecting to "
				    + server.getLocalSocketAddress() + "\nGoodbye!");
		       server.close();
		   }catch(SocketTimeoutException s)
		   {
		       System.out.println("Socket timed out! Starting again.");
		       break;
		   } catch (ClassNotFoundException ex) {
		   ex.printStackTrace();
	       }catch(IOException e)
		   {
		       e.printStackTrace();
		       break;
		   }
	   }
   }
    public static void main(String [] args)
    {
	int port = Integer.parseInt(args[0]);
	try
	    {
		Thread t = new LinkServer(port);
		t.start();
	    }catch(IOException e)
	    {
		e.printStackTrace();
	    }
    }
}
