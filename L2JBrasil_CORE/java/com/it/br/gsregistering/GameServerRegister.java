package com.it.br.gsregistering;

import java.io.IOException;
import java.io.InputStreamReader;
import java.io.LineNumberReader;
import java.math.BigInteger;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.util.Map;

import com.it.br.Config;
import com.it.br.L2DatabaseFactory;
import com.it.br.Server;
import com.it.br.gameserver.LoginServerThread;
import com.it.br.loginserver.GameServerTable;

public class GameServerRegister {
    private static String _choice;
    private static boolean _choiceOk;

    public static void main(String[] args) throws IOException {
        Server.serverMode = Server.MODE_LOGINSERVER;

        Config.load();

        LineNumberReader _in = new LineNumberReader(new InputStreamReader(System.in));
        try {
            GameServerTable.load();
        } catch (Exception e) {
            System.out.println("FATAL: Falha ao carregar o GameServerTable. Razão:" + e.getMessage());
            e.printStackTrace();
            System.exit(1);
        }

        GameServerTable gameServerTable = GameServerTable.getInstance();
        System.out.println("#--------------------------------------------------------------------");
        System.out.println("# Welcome to L2JBrasil GameServer Regitering ");
        System.out.println("#--------------------------------------------------------------------");
        System.out.println("# Digite o ID do servidor que você deseja registrar");
        System.out.println("# Digite 'help' ou 'ajuda' para obter uma lista de IDs.");
        System.out.println("# Digite 'clean' ou 'l2jbrasil' para cancelar o registro de todos os");
        System.out.println("# servidores de registro atualmente registrados neste LoginServer.");
        System.out.println("#-------------------------------------------------------------------");

        while (!_choiceOk) {
            System.out.println("Sua escolha:");
            _choice = _in.readLine();
            if ((_choice.equalsIgnoreCase("help")) || (_choice.equalsIgnoreCase("ajuda"))) {
                for (Map.Entry<Integer, String> entry : gameServerTable.getServerNames().entrySet()) {
                    System.out.println("Server: ID: " + entry.getKey() + "\t- " + entry.getValue() + " - Em Uso: " + (gameServerTable.hasRegisteredGameServerOnId(entry.getKey()) ? "YES" : "NO"));
                }
                System.out.println("Voce pode ver tambem em servername.xml");
            } else if ((_choice.equalsIgnoreCase("clean")) || (_choice.equalsIgnoreCase("l2jbrasil"))) {
                System.out.print("Isso vai cancelar o registro de todos os servidores deste servidor de login. Você tem certeza? (y/n)");
                System.out.print(" Voce tem certeza? (y/n),(s/n)");
                _choice = _in.readLine();
                if ((_choice.equals("y")) || (_choice.equals("s"))) {
                    GameServerRegister.cleanRegisteredGameServersFromDB();
                    gameServerTable.getRegisteredGameServers().clear();
                } else {
                    System.out.println("ABORTED");
                }
            } else {
                try {
                    int id = new Integer(_choice).intValue();
                    int size = gameServerTable.getServerNames().size();

                    if (size == 0) {
                        System.out.println("Nenhum nome de servidor disponível, certifique-se de que servername.xml esteja no diretório LoginServer.");
                        System.exit(1);
                    }

                    String name = gameServerTable.getServerNameById(id);
                    if (name == null) {
                        System.out.println("Nenhum nome para id: " + id);
                        continue;
                    } else {
                        if (gameServerTable.hasRegisteredGameServerOnId(id)) {
                            System.out.println("Este id ja esta em uso");
                        } else {
                            byte[] hexId = LoginServerThread.generateHex(16);
                            gameServerTable.registerServerOnDB(hexId, id, "");
                            Config.saveHexid(id, new BigInteger(hexId).toString(16), "hexid.txt");
                            Config.saveHexid(id, new BigInteger(hexId).toString(16), "../Game/config/other/hexid.txt");

                            System.out.println("Server Registrado hexid salvo para 'hexid.txt'");
                            System.out.println("Coloque este arquivo na pasta /config/other do seu servidor de games e renomeie-o para 'hexid.txt'");
                            System.out.println("Se o nome da sua pasta for Game o arquivo hexid ja esta no local adequado 'Game/config/other/hexid.txt'");
                            return;
                        }
                    }
                } catch (NumberFormatException nfe) {
                    System.out.println("Por Favor, Digite um numero ou 'help'");
                }
            }
        }
    }

    public static void cleanRegisteredGameServersFromDB() {
        Connection con = null;
        PreparedStatement statement = null;
        try {
            con = L2DatabaseFactory.getInstance().getConnection();
            statement = con.prepareStatement("DELETE FROM gameservers");
            statement.executeUpdate();
            statement.close();
        } catch (SQLException e) {
            System.out.println("Erro de SQL ao limpar os servidores registrados: " + e);
        } finally {
            try {
                statement.close();
            } catch (Exception e) {
            }
            try {
                con.close();
            } catch (Exception e) {
            }
        }
    }
}